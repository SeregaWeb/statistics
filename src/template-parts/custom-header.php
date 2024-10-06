<?php
/**
 * Custom header template
 *
 * @package WP-rock
 */

global $global_options;

if(!is_user_logged_in()) {
    echo '<h1>Need auth</h1>';
    die;
}

$helper = new TMSReportsHelper();
$array_tables = $helper->tms_tables;
$user_id = get_current_user_id();
$user_name = $helper->get_user_full_name_by_id($user_id);


$view_tables = get_field('permission_view', 'user_'.$user_id);
$curent_tables = get_field('current_select', 'user_'.$user_id);
?>

<header id="site-header" class="site-header js-site-header">
    <div class="container-fluid">
        <div class="row justify-content-between align-items-center pt-2 pb-2">
            <div class="col main-menu js-main-menu order-2 d-flex gap-2 justify-content-end align-items-center">
                <button class="btn btn-outline-primary js-open-popup-activator" data-href="#popup_add_company">Add broker</button>
                <button class="btn btn-outline-primary js-open-popup-activator" data-href="#popup_add_shipper">Add shipper</button>
                <?php if(is_array($view_tables) && sizeof($view_tables) > 0) ?>
                <div class="w-25">
                    <select class="form-select js-select-current-table" aria-label="Default select example">
                        <?php if(is_array($array_tables)): ?>
                            <?php foreach($array_tables as $val):
                                $view = array_search($val, $view_tables);
                                if (is_numeric($view)):  ?>
                                    <option <?php echo $curent_tables === $val ? 'selected' : ''; ?> value="<?php echo $val;?>"><?php echo $val;?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div>
                    <p class="m-0"><?php echo $user_name['full_name']; ?></p>
                </div>
            </div>
            <div class="col-auto order-1">
               <H2>TMS Portal</H2>
            </div>
            <div class="col-auto d-lg-none order-2">
                <button type="button" class="menu-btn js-menu-btn" aria-label="Menu" title="Menu" data-role="menu-action"></button>
            </div>
        </div>
    </div>
</header>
