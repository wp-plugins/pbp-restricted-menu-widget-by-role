<?php
/**
 * Plugin Name: PBP Restricted Menu-Widget by role
 * Plugin URI: http://projoktibangla.net
 * Description: Display menu or widget items based on if a user is logged in, logged out or both.
 * Version: 1.1
 * Author: projoktibangla
 * Author URI: http://projoktibangla.net
 * License: GPL2


    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/


/**
* Don't display if wordpress admin class is not found
* Protects code if wordpress breaks
*/
if ( ! function_exists( 'is_admin' ) ) {
    header( 'Status: 403 Forbidden' );
    header( 'HTTP/1.1 403 Forbidden' );
    exit();
}

/**
* Load Custom Walker class
*/
include('customWalker.php');



/**
* Create class privMenu() to prevent any function name conflicts with other WordPress plugins or the WordPress core.
* @since 0.1
*/
class privMenu {




    	/**
     	* Removes items from the menu displayed to the user if that menu item has been denied access to them in the admin panel
     	* @since 0.2
     	*/
	function remove_menu_items( $items, $menu, $args ) {

    		foreach ( $items as $key => $item ) {
			$meta_data = get_post_meta( $item->ID, '_priv_menu_role', true);
          		switch( $meta_data ) {
				case 'admin':
					$visible = current_user_can( 'manage_options' ) ? true : false;
					break;
            			case 'in' :
              				$visible = is_user_logged_in() ? true : false;
              				break;
            			case 'out' :
              				$visible = ! is_user_logged_in() ? true : false;
              				break;
            			default:
	      				$visible = true;
				/*
              				$visible = false;
              				if ( is_array( $item->roles ) && ! empty( $item->roles ) ) foreach ( $item->roles as $role ) {
                				if ( current_user_can( $role ) ) $visible = true;
              				}
				*/
              				break;
          		}
          		// add filter to work with plugins that don't use traditional roles
          		$visible = apply_filters( 'nav_menu_roles_item_visibility', $visible, $item );

          		if ( ! $visible ) unset( $items[$key] ) ;
    		}

    		return $items;
	}

    /**
     * Replace the default Admin Menu Walker
     * 
     */
    function edit_priv_menu_walker( $walker, $menu_id ) {
        return 'Priv_Menu_Walker';
    }



    /**
     * Save users selection in DataBase as post_meta on return of data from users browser
     * @since 0.2
     */
    function save_extra_menu_opts( $menu_id, $menu_item_db_id, $args ) {
        global $wp_roles;

        $allowed_roles = apply_filters( 'priv_menu_roles', $wp_roles->role_names );

        // verify this came from our screen and with proper authorization.
        if ( ! isset( $_POST['priv-menu-role-nonce'] ) || ! wp_verify_nonce( $_POST['priv-menu-role-nonce'], 'priv-menu-nonce-name' ) )
            return;

        $saved_data = false;

        if ( isset( $_POST['priv-menu-logged-in-out'][$menu_item_db_id]  )  && in_array( $_POST['priv-menu-logged-in-out'][$menu_item_db_id], array( 'in', 'out', 'admin') ) ) {
              $saved_data = $_POST['priv-menu-logged-in-out'][$menu_item_db_id];
        } elseif ( isset( $_POST['priv-menu-role'][$menu_item_db_id] ) ) {
            $custom_roles = array();
            // only save allowed roles
            foreach( $_POST['priv-menu-role'][$menu_item_db_id] as $role ) {
                if ( array_key_exists ( $role, $allowed_roles ) ) $custom_roles[] = $role;
            }
            if ( ! empty ( $custom_roles ) ) $saved_data = $custom_roles;
        }

        if ( $saved_data ) {
            update_post_meta( $menu_item_db_id, '_priv_menu_role', $saved_data );
        } else {
            delete_post_meta( $menu_item_db_id, '_priv_menu_role' );
        }
    }



} //End of privMenu() class



/**
* Define the Class
* @since 0.1
*/
$myprivMenuClass = new privMenu();


/**
* Action of what function to call to save users selection when returned from their browser
* @since 0.1
*/
add_action( 'wp_update_nav_menu_item', array( $myprivMenuClass, 'save_extra_menu_opts'), 10, 3 );


/**
* Replace the default Admin Menu Walker with the custom one from the customWalker.php file
* @since 0.1
*/
add_filter( 'wp_edit_nav_menu_walker', array( $myprivMenuClass, 'edit_priv_menu_walker' ), 10, 2 );


/**
* If is_admin() is not defined (User not in admin panel) then filter the displayed menu through the below function.
* @since 0.2
*/
if ( ! is_admin() ) {
        // add meta to menu item
	add_filter( 'wp_get_nav_menu_items', array($myprivMenuClass, 'remove_menu_items'), null, 3 );
}



/**
* Don't display if wordpress admin class is not found
* Protects code if wordpress breaks
* @since 0.1
*/
if ( ! function_exists( 'is_admin' ) ) {
    header( 'Status: 403 Forbidden' );
    header( 'HTTP/1.1 403 Forbidden' );
    exit();
}

class privWidget {

function privilege_widget_form_extend( $instance, $widget ) {
 		$row .= "\tid_base}[{$widget->number}][classes]'  id='widget-{$widget->id_base}-{$widget->number}-classes'  class='widefat'>\n";
		
                /* Get the roles saved for the post. */
                //$roles = get_post_meta( $item->ID, '_priv_widget', true );

                //$logged_in_out = ! is_array( $roles ) ? $roles : false;
	$privWidget_id = $widget->id_base."-".$widget->number;

	

	$logged_in_out = get_option($privWidget_id.'_priv_widget');
?>

                <input type="hidden" name="priv-widget-nonce" value="<?php echo wp_create_nonce( 'priv-widget-nonce-name' ); ?>" />
                <div class="field-priv_widget_role priv_widget_logged_in_out_field description-wide" style="margin: 5px; padding-bottom: 5px; overflow: hidden; ">
                    <span class="description"><?php _e( "Restricted by role", 'priv-widget' ); ?></span>
                    <br /> <br />

                    <input type="hidden" class="widget-id" value="<?php echo $privWidget_id ;?>" />

                    <div class="logged-input-holder" style="float: left; width: 35%;">
                        <input type="radio" class="widget-logged-in-out" name="priv-widget-logged-in-out[<?php echo $privWidget_id ;?>]" id="priv_widget_logged_out-for-<?php echo $privWidget_id ;?>" <?php checked( 'admin', $logged_in_out ); ?> value="admin" />
                        <label for="priv_widget_admin_user-for-<?php echo $privWidget_id ;?>">
                            <?php _e( 'For Admin', 'priv-widget'); ?>
                        </label>
                    </div>

                    <div class="logged-input-holder" style="float: left; width: 35%;">
                        <input type="radio" class="widget-logged-in-out" name="priv-widget-logged-in-out[<?php echo $privWidget_id ;?>]" id="priv_widget_logged_out-for-<?php echo $privWidget_id ;?>" <?php checked( 'out', $logged_in_out ); ?> value="out" />
                        <label for="priv_widget_logged_out-for-<?php echo $privWidget_id ;?>">
                            <?php _e( 'Logged Out Users', 'priv-widget'); ?>
                        </label>
                    </div>

                    <div class="logged-input-holder" style="float: left; width: 35%;">
                        <input type="radio" class="widget-logged-in-out" name="priv-widget-logged-in-out[<?php echo $privWidget_id ;?>]" id="priv_widget_logged_in-for-<?php echo $privWidget_id ;?>" <?php checked( 'in', $logged_in_out ); ?> value="in" />
                        <label for="priv_widget_logged_in-for-<?php echo $privWidget_id ;?>">
                            <?php _e( 'Logged In Users', 'priv-widget'); ?>
                        </label>
                    </div>

                    <div class="logged-input-holder" style="float: left; width: 30%;">
                        <input type="radio" class="widget-logged-in-out" name="priv-widget-logged-in-out[<?php echo $privWidget_id ;?>]" id="priv_widget_by_role-for-<?php echo $privWidget_id ;?>" <?php checked( '', $logged_in_out ); ?> value="" />
                        <label for="priv_widget_by_role-for-<?php echo $privWidget_id ;?>">
                            <?php _e( 'For All', 'priv-widget'); ?>
                        </label>
                    </div>

                </div>

<?php
 		return $instance;
	}

/**
* Save the data returned from the users browser in the database
* @since 0.1
*/
function privilege_widget_update($instance, $new_instance, $old_instance) {

        $opt_arr = $_POST['priv-widget-logged-in-out'];
	foreach ($opt_arr as $key => $value) {

        	if ( !empty($value) ) {
			// Save the posted value in the database
            		update_option( $key.'_priv_widget', $value );
        	} else {
			// Remove if option has no value when posted
            		delete_option( $key.'_priv_widget' );
       		 }
	}

	return $instance;
}


	
/**
* Modify's the widget data with the options for privilege widget
* @since 0.1
*/
function privilege_widget_filter( $widget )
{

	foreach($widget as $widget_area => $widget_list)
	{
		
		if ($widget_area=='wp_inactive_widgets' || empty($widget_list)) continue;

		foreach($widget_list as $pos => $widget_id)
		{
			$logged_in_out = get_option($widget_id.'_priv_widget');
                        switch( $logged_in_out ) {
                                case 'admin':
                                        $visible = current_user_can( 'manage_options' ) ? true : false;
                                        break;
                                case 'in' :
                                        $visible = is_user_logged_in() ? true : false;
                                        break;
                                case 'out' :
                                        $visible = ! is_user_logged_in() ? true : false;
                                        break;
                                default:
                                        $visible = true;
			}
			if ( ! $visible ) unset($widget_list[$pos]);
		}
		$widget[$widget_area] = $widget_list;
	}
    return $widget;
}

}

/**
* Define the Class
* @since 0.1
*/
$myprivWidgetClass = new privWidget();

/**
* Filter of what function to call to modify the widget output before it is returned to the users browser
* @since 0.1
*/
add_filter( 'sidebars_widgets', array($myprivWidgetClass, 'privilege_widget_filter'), 10);

/**
* Filter of what function to call to modify widget code
* @since 0.1
*/
add_filter('widget_form_callback', array($myprivWidgetClass, 'privilege_widget_form_extend'), 20, 2);


/**
* Filter of what function to call to write returned data to the database
* @since 0.1
*/
add_filter( 'widget_update_callback', array($myprivWidgetClass, 'privilege_widget_update'), 10, 3 );

?>
