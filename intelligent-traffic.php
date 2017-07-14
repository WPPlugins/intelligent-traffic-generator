<?php
/**
Plugin Name: Intelligent Traffic Generator
Plugin URI: http://www.locoed.com/wordpress-plugin/intelligent-traffic-generator-plugin-for-wordpress/
Description: Encourage the visitors to your website to publish the image from your post on their blog or on forums they use with ready made, cut and paste codes that contain a backlink to your website.
Author: Locoed Web Developement
Version: 0.4.3
Author URI: http://www.locoed.com/
*/

define('PLUGIN_WEBSITE', 'http://www.locoed.com/wordpress-plugin/intelligent-traffic-generator-plugin-for-wordpress/');

if (!is_plugin_page()) {

    add_action('admin_menu', 'mm_plugin_admin');
    
    function mm_plugin_admin() {
    	add_options_page('Locoed Intelligent Traffic Generator', 'Intelligent Traffic', 10, __FILE__, 'mm_options_page');
       
        $wlg_options = array(
            'cols' => 25,
            'rows' => 3,
            'text' => 'You can post this &#34;%%post_title%%&#34; image that&#39;s above on your blog/forum using following codes:',
            'link' => 'no'
        );
        add_option('wlg_options', $wlg_options);
    }
}

add_filter("plugin_action_links", 'AddPluginMy_ActionLink', 10, 2);

function AddPluginMy_ActionLink( $links, $file ) {
	    static $this_plugin;		
		if ( ! $this_plugin ) $this_plugin = plugin_basename(__FILE__);
       
		
        if ( $file == $this_plugin ) {
        	 //print("<pre>".print_r($links,true)."\n".$file."==".$this_plugin."</pre>");
			$settings_link = "<a href='".admin_url( "options-general.php?page=".$this_plugin )."'>". __('Settings') ."</a>";
			array_unshift( $links, $settings_link );
		}
        //exit("<pre>".print_r($links,true)."</pre>");
		return $links;
	}

function mm_init_link_gen($content) {
    global $wpdb, $post;

    if (is_singular()) {
        
        if (isset($_GET['attachment_id']) || get_query_var('attachment') != '') {
            $attachment_id = isset($_GET['attachment_id']) ? (int) $_GET['attachment_id'] : $wpdb->get_var('select ID from '. $wpdb->posts .' where post_name="'.get_query_var('attachment').'" and post_type="attachment"');
            $condition = 'ID = '.$attachment_id;
            // get the parent post
            $p = get_post($post->post_parent);
            $post_content = $p->post_content;

        } else {
            
            $condition = 'post_parent = '. $post->ID;
            $post_content = $post->post_content;
        }

        $post_title = $post->post_title;
        
        $attachment = $wpdb->get_results('SELECT ID, post_title, post_mime_type FROM '. $wpdb->posts .' WHERE '.$condition.' AND post_status = "inherit" AND post_type="attachment" ORDER BY post_date ASC LIMIT 1');

        $attachment = $attachment[0];

        if ($attachment->ID != '' && preg_match('/image/i', $attachment->post_mime_type)) {
            
            // attachment path & metadata 
            //$attach = wp_get_attachment_url($attachment->ID);
            $file_path   = get_post_meta($attachment->ID, '_wp_attached_file', true);
            $meta_values = get_post_meta($attachment->ID, '_wp_attachment_metadata');

            // get image name
            $img_array  = explode('/', $file_path);
            $image_name = $img_array[2]; 
            
            $folder = str_replace($image_name, '', wp_get_attachment_url($attachment->ID));
            $image = $folder.(isset($meta_values[0]['sizes']['medium']) ? $meta_values[0]['sizes']['medium']['file'] : $image_name);
            
            $wlg_options = get_option('wlg_options');
            
            $blog_url = get_bloginfo('url');
            
            if ('/' != $blog_url[strlen($blog_url)-1]) {
                $blog_url = $blog_url.'/';
            }
            
            $website_code = '<a href="'.get_permalink().'"><img src="'.$image.'" alt="'.$post_title.'"></a><br /><font size="1"><a href="'.get_permalink().'">'.$post_title.'</a> Hosted by <a href="'.$blog_url.'">'.get_bloginfo('blog_title').'</a></font>';
            
            $forum_code = '[url='.get_permalink().'][img]' .$image. '[/img][/url]';
            
            $custom_text = str_replace('%%post_title%%', $post_title, $wlg_options['text']);
            $custom_text = str_replace('%%img_title%%', $attachment->post_title, $custom_text);
            
            $out = "\n".'<!-- Start Locoed Intelligent Traffic Generator -->'."\n";
            $out .= '<!-- '.$image.' -->'."\n";

            $out .= '<div id="mm-link-gen">
            <p style="text-align: left;">'.stripslashes($custom_text).'</p>
            <div id="align-left"><h4>Website Code</h4>
            <textarea style="overflow: hidden;" cols="'.$wlg_options['cols'].'" rows="'.$wlg_options['rows'].'" onclick="this.focus();this.select();">'. htmlspecialchars($website_code).'</textarea></div>
            <div id="align-right"><h4>Forum Code</h4>
            <textarea style="overflow: hidden;" cols="'.$wlg_options['cols'].'" rows="'.$wlg_options['rows'].'" onclick="this.focus();this.select();">'. $forum_code.'</textarea></div>
            <div style="clear: both;"></div>';
            
            
            if ('yes' == $wlg_options['link']) {
                $out .= '<div id="linkback">Codes by <a href="'.PLUGIN_WEBSITE.'">Locoed Intelligent Traffic Generator</a></div>';
            }
            $out .= '</div>';
            $out .= "\n".'<!-- End Locoed Intelligent Traffic Generator -->'."\n";
        }
    }
    return $content.$out;
}

function mm_options_page() {
	//?page=intelligent-traffic-generator/intelligent-traffic.php
    //exit($action_url);
    $action_url = $_SERVER['PHP_SELF'] . '?page=intelligent-traffic-generator/' . basename(__FILE__);
    //exit($action_url);
    $wlg_options = get_option('wlg_options');
    if (isset($_POST['Submit'])) {
        $wlg_options = array(
                'cols' => $_POST['cols'],
                'rows' => $_POST['rows'],
                'text' => $_POST['text'],
                'link' => !isset($_POST['link']) ? 'no' : (string) $_POST['link']
            );
        if (update_option('wlg_options', $wlg_options)) {
            ?>
           
            <div id="message" class="updated fade"><p><strong>Settings saved.</strong></p></div>
            
        <?php
        }
    }
    
?>
	<div class='wrap'>
    <div id="icon-options-general" class="icon32"><br /></div>

		<h2>Locoed Intelligent Traffic Generator</h2>

            
            <h3>Customize Size of Text Areas to Fit Your Blog:</h3>

            <form action="<?php echo $action_url; ?>" method="post">
   
            <table class="form-table">
            <tr valign="top">
                <th scope="row">Number of Columns:</th>
                <td><input name="cols" id="cols" type="text" value="<?php echo $wlg_options['cols']; ?>" size="3" /></td>
            </tr>
            
            <tr valign="top">
                <th scope="row">Number of Rows:</th>
                <td><input name="rows" id="rows" type="text" value="<?php echo $wlg_options['rows']; ?>" size="3" /></td>
            </tr>
            </table>
            
            <h3>Customize Text to Go With Codes:</h3>

            <table class="form-table">
            <tr valign="top">
                <th scope="row">
	                <strong>%%post_title%%</strong><br /><small>will add post title to text</small><br /><br />  
                    <strong>%%img_title%%</strong><br /><small>will add image title to text</small>
              </th>
                <td><textarea name="text" id="text" cols="40" rows="4"><?php echo stripslashes($wlg_options['text']); ?></textarea></td>
            </tr>
            </table>
             
            <h3>Help Promote Locoed Intelligent Traffic Generator?</h3>

            <p><input type="checkbox" name="link" value="yes" <?php echo $wlg_options['link'] == 'yes' ? 'checked="checked"' : '' ?>/>&nbsp;&nbsp;Place a small support link to plug-in author on pages with generated codes. Thank you for your support.</p>
             
            <p class="submit">
                <input type="submit" name="Submit" class="button-primary" value="Save Changes" />
                </p>

            </form>
		
            <div id="donate-box">
                
                <p>Locoed Intelligent Traffic Generator was created with you in mind. Its goal is to drive heaps
                of traffic to your site both through direct links as well as through
                higher search engine rankings. The effects of using Locoed Intelligent Traffic Generator are
                long term and result in higher popularity and increased revenue for the
                lifetime of your blog.</p>
    
                <p>I couldn't do it without your support. If you like the plug-in, please
                consider a donation. It will be used for further development of the plug
                in so it can generate more traffic and more money back to you.</p>
                 
            
                <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
                <input type="hidden" name="cmd" value="_s-xclick">
                <input type="hidden" name="hosted_button_id" value="5141513">
                <input type="image"
                src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0"
                name="submit" alt="PayPal - The safer, easier way to pay online!">
                <img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif"
                width="1" height="1">
                </form>
            </div>
			
	</div>
<?php
}

function mm_wp_style() {
    echo "\n\n".'<style type="text/css"> 
        #mm-link-gen { width: 99%; margin: 20px auto 0px auto; text-align: center; }
        #mm-link-gen #align-left { float: left; }
        #mm-link-gen #align-right { float: right; }
        #mm-link-gen #linkback { float: left; font-size: 11px; }
        #mm-link-gen h4 { margin: 3px 0; }
        </style>'."\n\n";
}

function mm_admin_style() {
    echo "\n\n".'<style type="text/css"> 
        #donate-box { 
            width: 450px;
            margin: 20px 0;
            padding: 5px 10px; 
            background-color: #FFF6E3; 
            border: 1px dotted #2681AC;
            text-align: left;
            color: #000;
        }
        </style>'."\n\n";
}

/* Actions & Filters
**/

add_action('wp_head', 'mm_wp_style');
add_action('admin_head', 'mm_admin_style');

add_filter('the_content', 'mm_init_link_gen');
?>
