<?php
/**
 * The template for displaying vendor dashboard header content
 *
 * This template can be overridden by copying it to yourtheme/dc-product-vendor/vebdor-dashboard/dashboard-header.php.
 *
 * HOWEVER, on occasion WC Marketplace will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @author  WC Marketplace
 * @package WCMp/Templates
 * @version 3.0.0
 */
if (!defined('ABSPATH')) {
    exit;
}
global $WCMp ,$wp;
session_start();
$vendor = get_wcmp_vendor(get_current_vendor_id());
$author_id=get_current_vendor_id();
$address1 = get_user_meta($author_id,'_vendor_address_1',true);
$address2 = get_user_meta($author_id,'_vendor_address_2',true);
$city = get_user_meta($author_id,'_vendor_city',true);
$state = get_user_meta($author_id,'_vendor_state',true);
$postcode = get_user_meta($author_id,'_vendor_postcode',true);
$vendor_address=$address1.' '.$address2.' '.$city.' '.$state.' '.$postcode;
if($address1=='' && $address2 == '' && $city== '' && $state == '' && $postcode ==''){
     ob_start();
     $panel = $WCMp->vendor_dashboard->dashboard_header_right_panel_nav();
     $url=$panel['storefront']['url'];
     $url_end=strtolower($panel['storefront']['label']);
     $current_url=home_url( $wp->request );
     $current_end=end(explode("/", $current_url));
     if($current_end != $url_end){
         
         if(!isset($_SESSION['set_redirect_once']) || $_SESSION['set_redirect_once'] != 'redirected' ){
            $_SESSION['set_redirect_once']='redirect'; 
            $_SESSION['set_redirect_message']='Please set address!';
           wp_redirect($url);
           exit; 
       }
       else{
        $_SESSION['set_redirect_message']='';    
       }
     }
     else{
      $_SESSION['set_redirect_once']='redirected';  
     }
}   
else{
  unset($_SESSION['set_redirect_message']);  
  unset($_SESSION['set_redirect_once']);
}

if($vendor) {
	$vendor_logo = $vendor->profile_image ? wp_get_attachment_url($vendor->profile_image) : get_avatar_url(get_current_vendor_id(), array('size' => 80));
} else {
    $vendor_logo = get_avatar_url(get_current_vendor_id(), array('size' => 80));
}
$site_logo = get_wcmp_vendor_settings('wcmp_dashboard_site_logo', 'vendor', 'dashboard') ? get_wcmp_vendor_settings('wcmp_dashboard_site_logo', 'vendor', 'dashboard') : '';
?>
<style>
    span.show_message {   
    color: red;
    font-size: 18px;
    display: inline-block;
    padding-left: 82px;
    padding-top: 21px;
}

@media screen and (max-width:767px){
span.show_message {   
    position: absolute;
    z-index: 999;   
    font-size: 13px;   
    padding-top: 20px;
    background: #fff;
    padding-bottom: 19px;
    padding-left: 7px;
}
}
</style>
<!-- Top bar -->
<div class="top-navbar white-bkg">
    <div class="navbar navbar-default">
        <span class="show_message"><?php echo $_SESSION['set_redirect_message']; ?></span>
        <div class="topbar-left pull-left pos-rel">
            <div class="site-logo text-center pos-middle">
                <a href="<?php echo apply_filters('wcmp_vendor_dashboard_header_site_url', site_url(), $vendor); ?>">
                    <?php if ($site_logo) { ?>
                        <img src="<?php echo get_url_from_upload_field_value($site_logo); ?>" alt="<?php echo bloginfo(); ?>">
                    <?php } else {
                        echo bloginfo();
                    } ?>
                </a>
            </div>
        </div>
        <ul class="nav pull-right top-user-nav">
            <li class="dropdown login-user">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                    <i class="wcmp-font ico-user-icon"></i>
                    <span><i class="wcmp-font ico-down-arrow-icon"></i></span>
                </a>
                <ul class="dropdown-menu dropdown-user dropdown-menu-right">
                    <li class="sidebar-logo text-center"> 
                        <div class="vendor-profile-pic-holder">
                            <img src="<?php echo $vendor_logo; ?>" alt="vendor logo">
                        </div>
                        <h4><?php
                            if ($vendor) {
                                echo $vendor->user_data->data->display_name;
                            } else {
                                $user = wp_get_current_user();
                                echo $user->data->user_email;
                            }
                            ?></h4>  
                    </li> 
                    <?php
                    $panel_nav = $WCMp->vendor_dashboard->dashboard_header_right_panel_nav();
                    if ($panel_nav) :
                        if (!$vendor) {
                            unset($panel_nav['storefront']);
                            unset($panel_nav['wp-admin']);
                            unset($panel_nav['profile']);
                        }
                        sksort($panel_nav, 'position', true);
                        foreach ($panel_nav as $key => $nav):
                            if (current_user_can($nav['capability']) || $nav['capability'] === true):
                                ?>
                                <li class="<?php if (!empty($nav['class'])) echo $nav['class']; ?>"><a href="<?php echo esc_url($nav['url']); ?>" target="<?php echo $nav['link_target']; ?>"><i class="<?php echo $nav['nav_icon']; ?>"></i> <span><?php echo $nav['label']; ?></span></a></li>
                            <?php
                            endif;
                        endforeach;
                    endif;
                    ?>

<?php do_action('wcmp_dashboard_header_right_vendor_dropdown'); ?>
                </ul>
                <!-- /.dropdown -->
            </li>
        </ul>

        <?php
        if ($vendor)
            $header_nav = $WCMp->vendor_dashboard->dashboard_header_nav();
        else
            $header_nav = false;

        if ($header_nav) :
            sksort($header_nav, 'position', true);
            ?>
            <ul class="nav navbar-top-links navbar-right pull-right btm-nav-fixed">
                        <?php
                        foreach ($header_nav as $key => $nav):
                            if (current_user_can($nav['capability']) || $nav['capability'] === true):
                                ?>
                        <li class="notification-link <?php if (!empty($nav['class'])) echo $nav['class']; ?>">
                            <a href="<?php echo esc_url($nav['url']); ?>" target="<?php echo $nav['link_target']; ?>" title="<?php echo $nav['label']; ?>">
                                <i class="<?php echo $nav['nav_icon']; ?>"></i> <span class="hidden-sm hidden-xs"><?php echo $nav['label']; ?></span>
                        <?php
                        if ($key == 'announcement') :
                            $vendor_announcements = $vendor->get_announcements();
                            if (isset($vendor_announcements['unread']) && count($vendor_announcements['unread']) > 0) {
                                echo '<span class="notification-blink">'.count($vendor_announcements['unread']).'</span>';
                            }
                        endif;
                        ?>
                            </a>
                        </li>
            <?php
        endif;
    endforeach;
    ?>
            </ul>     
<?php endif; ?>
        <!-- /.navbar-top-links -->
    </div>
</div>