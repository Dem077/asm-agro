<?php

namespace App\Services;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class HrApiSyncService
{
    /**
     * @return array{
     *     error: bool,
     *     error_message?: string,
     *     summary?: array<string, int>
     * }
     */
    public function sync(): array
    {
        $config = config('services.hr_api');

        if (empty($config['base_url']) || empty($config['api_key'])) {
            return [
                'error' => true,
                'error_message' => trans('admin/users/message.hr_api_not_configured'),
            ];
        }

        try {
            $departments = $this->fetchDepartments($config);
            $users = $this->fetchUsers($config);
        } catch (RequestException $e) {
            return [
                'error' => true,
                'error_message' => trans('admin/users/message.hr_api_request_failed', [
                    'message' => $e->getMessage(),
                ]),
            ];
        } catch (\Throwable $e) {
            return [
                'error' => true,
                'error_message' => $e->getMessage(),
            ];
        }

        if ($users->isEmpty()) {
            return [
                'error' => true,
                'error_message' => trans('admin/users/message.hr_api_no_users'),
            ];
        }

        $users = $users->filter(fn ($row) => is_array($row));

        if ($users->isEmpty()) {
            return [
                'error' => true,
                'error_message' => trans('admin/users/message.hr_api_invalid_payload'),
            ];
        }

        $summary = [
            'departments_created' => 0,
            'departments_updated' => 0,
            'users_created' => 0,
            'users_updated' => 0,
            'users_skipped' => 0,
        ];

        $departmentMap = $this->syncDepartments($departments, $users, $summary);
        $this->syncUsers($users, $departmentMap, $summary);

        return [
            'error' => false,
            'summary' => $summary,
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function fetchUsers(array $config): Collection
    {
        return $this->fetchCollection($config, $config['users_path']);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function fetchDepartments(array $config): Collection
    {
        try {
            return $this->fetchCollection($config, $config['departments_path']);
        } catch (RequestException) {
            return collect();
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function fetchCollection(array $config, string $path): Collection
    {
        $url = $config['base_url'].$path;

        $response = Http::withHeaders([
            'X-API-KEY' => $config['api_key'],
            'Accept' => 'application/json',
        ])
            ->timeout(60)
            ->get($url);

        $response->throw();

        $payload = $this->parseJsonPayload($response->body());

        return $this->normalizeCollection($payload);
    }

    private function parseJsonPayload(string $body): mixed
    {
        $body = trim($body);

        if ($body === '') {
            return null;
        }

        $decoded = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // Some APIs prepend PHP/HTML warnings before JSON (breaks json_decode).
        $jsonStart = strpos($body, '[');
        if ($jsonStart === false) {
            $jsonStart = strpos($body, '{');
        }

        if ($jsonStart === false) {
            return null;
        }

        $decoded = json_decode(substr($body, $jsonStart), true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    private function normalizeCollection(mixed $payload): Collection
    {
        if (! is_array($payload)) {
            return collect();
        }

        if (array_is_list($payload)) {
            return collect($payload);
        }

        foreach (['data', 'users', 'departments', 'results', 'records', 'items'] as $key) {
            if (! isset($payload[$key]) || ! is_array($payload[$key])) {
                continue;
            }

            if (array_is_list($payload[$key])) {
                return collect($payload[$key]);
            }

            if (isset($payload[$key]['data']) && is_array($payload[$key]['data']) && array_is_list($payload[$key]['data'])) {
                return collect($payload[$key]['data']);
            }
        }

        return collect();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isActiveUser(array $row): bool
    {
        if (! array_key_exists('active', $row)) {
            return true;
        }

        return in_array($row['active'], [1, '1', true, 'true'], true);
    }

    /**
     * @param  array<string, int>  $summary
     * @return array<int, int> external department id => local department id
     */
    private function syncDepartments(Collection $departments, Collection $users, array &$summary): array
    {
        $map = [];

        $supportsExternalId = Schema::hasColumn('departments', 'external_id');

        $upsertDepartment = function (int $externalId, string $name) use (&$map, &$summary, $supportsExternalId) {
            $department = $supportsExternalId
                ? Department::where('external_id', $externalId)->first()
                : Department::where('name', $name)->first();

            if ($department) {
                if ($supportsExternalId && empty($department->external_id)) {
                    $department->external_id = $externalId;
                    $department->save();
                }
                if ($department->name !== $name) {
                    $department->name = $name;
                    $department->save();
                    $summary['departments_updated']++;
                }

                $map[$externalId] = $department->id;

                return;
            }

            $attributes = ['name' => $name];
            if ($supportsExternalId) {
                $attributes['external_id'] = $externalId;
            }

            $department = Department::create($attributes);

            $map[$externalId] = $department->id;
            $summary['departments_created']++;
        };

        $departmentNames = $this->collectDepartmentNames($departments, $users);

        foreach ($departmentNames as $externalId => $name) {
            $upsertDepartment($externalId, $name);
        }

        return $map;
    }

    /**
     * @return array<int, string> HR department id => name
     */
    private function collectDepartmentNames(Collection $departments, Collection $users): array
    {
        $names = [];

        foreach ($departments as $row) {
            if (! is_array($row)) {
                continue;
            }

            $externalId = (int) ($row['id'] ?? 0);
            $name = $this->resolveDepartmentName($row);

            if ($externalId > 0 && $name !== null) {
                $names[$externalId] = $name;
            }
        }

        foreach ($users as $user) {
            if (! is_array($user)) {
                continue;
            }

            $externalId = (int) ($user['department_id'] ?? 0);
            if ($externalId <= 0) {
                continue;
            }

            $nested = $user['department'] ?? null;
            if (! is_array($nested)) {
                continue;
            }

            $name = $this->resolveDepartmentName($nested);
            if ($name !== null) {
                $names[$externalId] = $name;
            }
        }

        return $names;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function resolveDepartmentName(array $row): ?string
    {
        foreach (['name', 'department_name', 'title', 'dept_name'] as $field) {
            $name = trim((string) ($row[$field] ?? ''));
            if ($name !== '') {
                return $name;
            }
        }

        return null;
    }

    /**
     * @param  array<int, int>  $departmentMap
     * @param  array<string, int>  $summary
     */
    private function syncUsers(Collection $users, array $departmentMap, array &$summary): void
    {
        foreach ($users as $row) {
            if (! $this->isActiveUser($row)) {
                $summary['users_skipped']++;

                continue;
            }

            $externalId = (string) ($row['id'] ?? '');
            $email = trim((string) ($row['email'] ?? ''));
            $empNo = trim((string) ($row['emp_no'] ?? ''));

            if ($externalId === '' && $email === '' && $empNo === '') {
                $summary['users_skipped']++;

                continue;
            }

            $user = $this->findLocalUser($externalId, $email, $empNo);
            $isNew = ! $user;

            if ($isNew) {
                $user = new User;
                $user->password = $user->noPassword();
                $user->activated = 1;
                $user->locale = app()->getLocale();
            }

            [$firstName, $lastName] = $this->splitName((string) ($row['name'] ?? ''));

            $user->scim_externalid = $externalId ?: $user->scim_externalid;
            $user->first_name = $firstName ?: ($user->first_name ?? 'User');
            $user->last_name = $lastName;
            $user->display_name = trim((string) ($row['name'] ?? '')) ?: $user->display_name;
            $user->email = $email ?: $user->email;
            $user->employee_num = $empNo ?: $user->employee_num;
            $user->jobtitle = (string) ($row['designation'] ?? $user->jobtitle);
            $user->phone = (string) ($row['phone'] ?? $user->phone);
            $user->mobile = (string) ($row['mobile'] ?? $user->mobile);
            $user->username = $this->ensureUniqueUsername(
                $this->resolveUsername($user, $email, $empNo, $externalId),
                $user->id
            );

            if (! empty($row['joined_date'])) {
                $user->start_date = $row['joined_date'];
            }

            $externalDepartmentId = (int) ($row['department_id'] ?? 0);
            if ($externalDepartmentId > 0 && isset($departmentMap[$externalDepartmentId])) {
                $user->department_id = $departmentMap[$externalDepartmentId];
            }

            if ($user->save()) {
                $isNew ? $summary['users_created']++ : $summary['users_updated']++;
            } else {
                $summary['users_skipped']++;
            }
        }
    }

    private function findLocalUser(string $externalId, string $email, string $empNo): ?User
    {
        if ($externalId !== '') {
            $match = User::withTrashed()->where('scim_externalid', $externalId)->first();
            if ($match) {
                if ($match->trashed()) {
                    $match->restore();
                }

                return $match;
            }
        }

        if ($empNo !== '') {
            $match = User::withTrashed()->where('employee_num', $empNo)->first();
            if ($match) {
                if ($match->trashed()) {
                    $match->restore();
                }

                if ($externalId !== '' && empty($match->scim_externalid)) {
                    $match->scim_externalid = $externalId;
                }

                return $match;
            }
        }

        if ($email !== '') {
            $match = User::withTrashed()->where('email', $email)->first();
            if ($match) {
                if ($match->trashed()) {
                    $match->restore();
                }

                if ($externalId !== '' && empty($match->scim_externalid)) {
                    $match->scim_externalid = $externalId;
                }

                return $match;
            }
        }

        return null;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $fullName): array
    {
        $fullName = trim($fullName);

        if ($fullName === '') {
            return ['', ''];
        }

        $parts = preg_split('/\s+/', $fullName, 2);

        return [
            $parts[0] ?? '',
            $parts[1] ?? '',
        ];
    }

    private function resolveUsername(User $user, string $email, string $empNo, string $externalId): string
    {
        if (! empty($user->username)) {
            return $user->username;
        }

        if ($email !== '' && str_contains($email, '@')) {
            return Str::before($email, '@');
        }

        if ($empNo !== '') {
            return 'emp'.$empNo;
        }

        return 'user'.$externalId;
    }

    private function ensureUniqueUsername(string $username, ?int $exceptUserId = null): string
    {
        $base = Str::limit($username, 180, '');
        $candidate = $base;
        $counter = 1;

        while (User::withTrashed()
            ->where('username', $candidate)
            ->when($exceptUserId, fn ($query) => $query->where('id', '!=', $exceptUserId))
            ->exists()) {
            $candidate = $base.$counter;
            $counter++;
        }

        return $candidate;
    }
}
