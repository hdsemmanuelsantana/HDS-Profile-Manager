<?php
/**
 * Author: Emmanuel Santana
 * Author URI: https://github.com/hdsemmanuelsantana/HDS-Profile-Manager
 * Description: Manage and display employee profiles via shortcode, grouped by tab and row.
 * Version: 1.0.0
 * Author: HDS Web Team
 */

// Register Custom Post Type
function hds_register_profile_post_type() {
    register_post_type('hds_profile', [
        'labels' => [
            'name' => 'Profiles',
            'singular_name' => 'Profile',
        ],
        'public' => true,
        'has_archive' => false,
        'show_in_menu' => true,
        'supports' => ['title', 'editor', 'thumbnail'],
        'menu_icon' => 'dashicons-id',
    ]);
}
add_action('init', 'hds_register_profile_post_type');

// Add custom meta boxes
function hds_add_profile_meta_boxes() {
    add_meta_box('hds_profile_meta', 'Profile Details', 'hds_render_profile_meta_box', 'hds_profile', 'normal', 'default');
}
add_action('add_meta_boxes', 'hds_add_profile_meta_boxes');

function hds_render_profile_meta_box($post) {
    $fields = [
        'image_url' => 'Image URL',
        'job_titles' => 'Job Titles (comma-separated)',
        'email' => 'Email',
        'phone' => 'Phone Number',
        'tabs' => 'Tabs (comma-separated)',
        'tab_rows' => 'Tab Rows (comma-separated)',
    ];
    foreach ($fields as $key => $label) {
        $value = get_post_meta($post->ID, $key, true);
        echo "<p><label for='$key'><strong>$label:</strong></label><br>";
        echo "<input type='text' id='$key' name='$key' value='" . esc_attr($value) . "' style='width:100%;'></p>";
    }
}

function hds_save_profile_meta($post_id) {
    $fields = ['image_url', 'job_titles', 'email', 'phone', 'tabs', 'tab_rows'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
}
add_action('save_post', 'hds_save_profile_meta');

// Shortcode to display profiles by tab
function hds_profiles_shortcode($atts) {
    $a = shortcode_atts(['tab' => ''], $atts);
    $query = new WP_Query([
        'post_type' => 'hds_profile',
        'posts_per_page' => -1
    ]);

    $profiles_by_row = [];
    ob_start();
    while ($query->have_posts()) {
        $query->the_post();
        $tabs = explode(',', get_post_meta(get_the_ID(), 'tabs', true));
        $rows = explode(',', get_post_meta(get_the_ID(), 'tab_rows', true));
        if (!in_array($a['tab'], array_map('trim', $tabs))) continue;
        $row = trim($rows[array_search($a['tab'], array_map('trim', $tabs))] ?? '1');
        $profiles_by_row[$row][] = get_the_ID();
    }
    wp_reset_postdata();

    $z_index = 9999;
    foreach ($profiles_by_row as $row => $profile_ids) {
        echo '<div class="profile-row">';
        foreach ($profile_ids as $pid) {
            $image = esc_url(get_post_meta($pid, 'image_url', true));
            $name = get_the_title($pid);
            $job_titles = explode(',', get_post_meta($pid, 'job_titles', true));
            $email = esc_html(get_post_meta($pid, 'email', true));
            $phone = esc_html(get_post_meta($pid, 'phone', true));
            $bio = apply_filters('the_content', get_post_field('post_content', $pid));

            echo "<div class='profile'>
                <img src='$image' alt='$name headshot' class='profile-image' />
                <h3>$name</h3>
                <h4>" . esc_html(trim($job_titles[0])) . "</h4>
                <div class='bio-icon' style='z-index: $z_index;'>
                  <div class='hover-trigger'>
                    <span class='et-icon-wrapper'><span class='et-pb-icon'>c</span></span>
                    <div class='flyout-container'>
                      <p class='contact-info'>$phone<br><a href='mailto:$email'>$email</a></p>
                      <div class='flyout-header'></div>
                      <div class='flyout-content'>$bio</div>
                    </div>
                  </div>
                </div>
              </div>";
            $z_index -= 100;
        }
        echo '</div>';
    }

    return ob_get_clean();
}
add_shortcode('hds_profiles', 'hds_profiles_shortcode');

// Inline CSS and JS
function hds_profile_styles_scripts() {
    echo '<style>';
    include plugin_dir_path(__FILE__) . 'css/hds-profiles.css';
    echo '</style>';

    echo '<script>
    document.addEventListener("DOMContentLoaded", function () {
        const bios = document.querySelectorAll(".bio-icon");
        let baseZ = 9999;
        bios.forEach((el, i) => {
            el.style.zIndex = baseZ - i * 100;
        });
    });
    </script>';
}
add_action('wp_footer', 'hds_profile_styles_scripts');
?>
