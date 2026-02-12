<?php

return array(

    'does_not_exist' => 'Asset Account does not exist.',
    'assoc_models'	 => 'This Asset Account is currently associated with at least one model and cannot be deleted. Please update your models to no longer reference this category and try again. ',
    'assoc_items'	 => 'This Asset Account is currently associated with at least one :asset_type and cannot be deleted. Please update your :asset_type  to no longer reference this category and try again. ',

    'create' => array(
        'error'   => 'Asset Account was not created, please try again.',
        'success' => 'Asset Account created successfully.'
    ),

    'update' => array(
        'error'   => 'Asset Account was not updated, please try again',
        'success' => 'Asset Account updated successfully.',
        'cannot_change_category_type'   => 'You cannot change the Asset Account type once it has been created',
    ),

    'delete' => array(
        'confirm'                => 'Are you sure you wish to delete this Asset Account?',
        'error'                  => 'There was an issue deleting the Asset Account. Please try again.',
        'success'                => 'Asset Account was deleted successfully.',
        'bulk_success'           => 'Asset Accounts were deleted successfully.',
        'partial_success'        => 'Asset Account deleted successfully. See additional information below. | :count Asset Accounts were deleted successfully. See additional information below.',
    )

);
