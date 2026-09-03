<?php
/*
Plugin Name: Dynamic CPT & Taxonomy Generator
Description: Create new CPTs with optional taxonomy or add taxonomy to existing CPTs. All managed from a single plugin.
Version: 1.0
Author: Rima Prajapati
Text Domain: dynamic-cpt-and-tax-generator
*/

if (!defined('ABSPATH')) exit;

class Dynamic_CPT_Taxonomy_Generator {

    private $errors = array();
    private $success = array();

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('init', array($this, 'register_saved_cpts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function add_menu() {
        add_menu_page(
            'Dynamic CPT & Taxonomy Generator',
            'CPT Generator',
            'manage_options',
            'dynamic-cpt-generator',
            array($this, 'admin_page'),
            'dashicons-admin-post',
            20
        );
    }

    public function enqueue_admin_scripts($hook) {
        if($hook != 'toplevel_page_dynamic-cpt-generator') return;
        // Add JS for showing/hiding fields based on action
        wp_enqueue_script('dcpt-admin-js', plugin_dir_url(__FILE__).'admin.js', array('jquery'), false, true);
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Dynamic CPT & Taxonomy Generator</h1>

            <?php
            // Display errors
            foreach($this->errors as $err) {
                echo '<div class="notice notice-error is-dismissible"><p>'.esc_html($err).'</p></div>';
            }
            // Display success messages
            foreach($this->success as $msg) {
                echo '<div class="notice notice-success is-dismissible"><p>'.esc_html($msg).'</p></div>';
            }
            ?>

            <form method="post">
                <?php wp_nonce_field('save_dcpt','dcpt_nonce'); ?>

                <h2>Choose Action</h2>
                <select name="dcpt_action" id="dcpt_action">
                    <option value="">-- Select --</option>
                    <option value="new_cpt">Create New CPT + Taxonomy</option>
                    <option value="add_taxonomy">Add Taxonomy to Existing CPT</option>
                </select>

                <div id="new_cpt_fields" style="display:none; margin-top:20px;">
                    <h3>New CPT + Taxonomy</h3>
                    <table class="form-table">
                        <tr>
                            <th>Post Type Slug</th>
                            <td><input type="text" name="cpt_slug"></td>
                        </tr>
                        <tr>
                            <th>Post Type Name</th>
                            <td><input type="text" name="cpt_name"></td>
                        </tr>
                        <tr>
                            <th>Post Type Icon (Dashicon)</th>
                            <td>
                                <input type="text" name="cpt_icon" placeholder="e.g., dashicons-book">
                                <p class="description">Enter a Dashicon class (e.g., dashicons-book, dashicons-portfolio).</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Taxonomy Slug (optional)</th>
                            <td><input type="text" name="tax_slug"></td>
                        </tr>
                        <tr>
                            <th>Taxonomy Name (optional)</th>
                            <td><input type="text" name="tax_name"></td>
                        </tr>
                    </table>
                </div>

                <div id="add_taxonomy_fields" style="display:none; margin-top:20px;">
                    <h3>Add Taxonomy to Existing CPT</h3>
                    <table class="form-table">
                        <tr>
                            <th>Select Existing CPT</th>
                            <td>
                                <select name="existing_cpt">
                                    <option value="">-- Select CPT --</option>
                                    <?php
                                    $existing_cpts = get_post_types(array('public'=>true), 'objects');
                                    $exclude = array('page','attachment');
                                    foreach($existing_cpts as $cpt) {
                                        if(in_array($cpt->name, $exclude)) continue;
                                        echo '<option value="'.$cpt->name.'">'.$cpt->labels->name.'</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>Taxonomy Slug</th>
                            <td><input type="text" name="existing_tax_slug"></td>
                        </tr>
                        <tr>
                            <th>Taxonomy Name</th>
                            <td><input type="text" name="existing_tax_name"></td>
                        </tr>
                    </table>
                </div>

                <?php submit_button('Save'); ?>
            </form>
        </div>
        <?php

        // Handle form submission
        if(isset($_POST['dcpt_action']) && check_admin_referer('save_dcpt','dcpt_nonce')) {
            $this->handle_form();
        }
    }

    private function handle_form() {
        $action = sanitize_text_field($_POST['dcpt_action']);
        $cpts = get_option('dcpt_saved_cpts', array());

        if($action == 'new_cpt') {
            $cpt_slug = sanitize_text_field($_POST['cpt_slug']);
            $cpt_name = sanitize_text_field($_POST['cpt_name']);
            $tax_slug = sanitize_text_field($_POST['tax_slug']);
            $tax_name = sanitize_text_field($_POST['tax_name']);
            $cpt_icon = sanitize_text_field($_POST['cpt_icon']);

            // Validation
            if(empty($cpt_slug)) $this->errors[] = "CPT slug is required.";
            elseif(!preg_match('/^[a-z0-9_-]+$/', $cpt_slug)) $this->errors[] = "CPT slug can only contain lowercase letters, numbers, dashes, and underscores.";
            if(post_type_exists($cpt_slug)) $this->errors[] = "CPT slug '{$cpt_slug}' already exists.";

            if(empty($cpt_name)) $this->errors[] = "CPT name is required.";

            if(empty($this->errors)) {
                $cpts[$cpt_slug] = array(
                    'name'=>$cpt_name,
                    'icon'=>$cpt_icon,
                    'taxonomy'=>array(
                        'slug'=>$tax_slug,
                        'name'=>$tax_name
                    )
                );
                update_option('dcpt_saved_cpts', $cpts);
                $this->success[] = "CPT '{$cpt_name}' saved successfully.";
            }
        }

        if($action == 'add_taxonomy') {
            $existing_cpt = sanitize_text_field($_POST['existing_cpt']);
            $existing_tax_slug = sanitize_text_field($_POST['existing_tax_slug']);
            $existing_tax_name = sanitize_text_field($_POST['existing_tax_name']);

            if(empty($existing_cpt)) $this->errors[] = "Please select a CPT.";
            if(empty($existing_tax_slug)) $this->errors[] = "Taxonomy slug is required.";
            elseif(taxonomy_exists($existing_tax_slug)) $this->errors[] = "Taxonomy slug '{$existing_tax_slug}' already exists.";
            if(empty($existing_tax_name)) $this->errors[] = "Taxonomy name is required.";

            if(empty($this->errors)) {
                if(!isset($cpts[$existing_cpt])) $cpts[$existing_cpt] = array('name'=>$existing_cpt);
                $cpts[$existing_cpt]['taxonomy'] = array(
                    'slug'=>$existing_tax_slug,
                    'name'=>$existing_tax_name
                );
                update_option('dcpt_saved_cpts', $cpts);
                $this->success[] = "Taxonomy '{$existing_tax_name}' added to CPT '{$existing_cpt}'.";
            }
        }
    }

    public function register_saved_cpts() {
        $cpts = get_option('dcpt_saved_cpts', array());
        if(!empty($cpts)) {
            foreach($cpts as $slug => $data) {
                // Register CPT
                if(!post_type_exists($slug)) {
                    register_post_type($slug, array(
                        'labels'=>array('name'=>$data['name'],'singular_name'=>$data['name']),
                        'public'=>true,
                        'has_archive'=>true,
                        'show_in_rest'=>true,
                        'menu_icon'=> !empty($data['icon']) ? $data['icon'] : 'dashicons-admin-post',
                        'supports'=>array('title','editor','thumbnail')
                    ));
                }

                // Register taxonomy
                if(!empty($data['taxonomy']['slug']) && !taxonomy_exists($data['taxonomy']['slug'])) {
                    register_taxonomy($data['taxonomy']['slug'], $slug, array(
                        'labels'=>array('name'=>$data['taxonomy']['name'],'singular_name'=>$data['taxonomy']['name']),
                        'public'=>true,
                        'hierarchical'=>true,
                        'show_in_rest'=>true
                    ));
                }
            }
        }
    }
}

new Dynamic_CPT_Taxonomy_Generator();
