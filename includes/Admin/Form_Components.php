<?php
/**
 * Reusable Form Components
 * 
 * @package SohojSecureOrder
 */

namespace SohojSecureOrder\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Form Components Helper Class
 */
class Form_Components {
    
    /**
     * Render a toggle switch
     * 
     * @param array $args Switch arguments
     * @return string HTML output
     */
    public static function render_switch($args = array()) {
        $defaults = array(
            'id' => '',
            'name' => '',
            'value' => 0,
            'checked' => false,
            'label' => '',
            'description' => '',
            'class' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        ob_start();
        ?>
        <div class="sohoj-form-field">
            <div class="sohoj-switch-container">
                <label class="sohoj-switch">
                    <input 
                        type="checkbox" 
                        id="<?php echo esc_attr($args['id']); ?>" 
                        name="<?php echo esc_attr($args['name']); ?>" 
                        value="<?php echo esc_attr($args['value']); ?>"
                        <?php checked($args['checked']); ?>
                        class="sohoj-switch-input <?php echo esc_attr($args['class']); ?>"
                    />
                    <span class="sohoj-switch-slider"></span>
                </label>
                <?php if (!empty($args['label'])): ?>
                    <label for="<?php echo esc_attr($args['id']); ?>" class="sohoj-switch-label">
                        <?php echo esc_html($args['label']); ?>
                    </label>
                <?php endif; ?>
            </div>
            <?php if (!empty($args['description'])): ?>
                <p class="sohoj-field-description"><?php echo wp_kses_post($args['description']); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render a form section
     * 
     * @param array $args Section arguments
     * @return string HTML output
     */
    public static function render_section($args = array()) {
        $defaults = array(
            'title' => '',
            'description' => '',
            'content' => '',
            'class' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        ob_start();
        ?>
        <div class="sohoj-form-section <?php echo esc_attr($args['class']); ?>">
            <?php if (!empty($args['title'])): ?>
                <h3 class="sohoj-section-title"><?php echo esc_html($args['title']); ?></h3>
            <?php endif; ?>
            
            <?php if (!empty($args['description'])): ?>
                <p class="sohoj-section-description"><?php echo wp_kses_post($args['description']); ?></p>
            <?php endif; ?>
            
            <div class="sohoj-section-content">
                <?php echo $args['content']; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render a button
     * 
     * @param array $args Button arguments
     * @return string HTML output
     */
    public static function render_button($args = array()) {
        $defaults = array(
            'text' => 'Submit',
            'type' => 'submit',
            'class' => 'primary',
            'size' => 'normal',
            'disabled' => false,
            'id' => '',
            'name' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $classes = array('sohoj-btn', 'sohoj-btn--' . $args['class'], 'sohoj-btn--' . $args['size']);
        
        ob_start();
        ?>
        <button 
            type="<?php echo esc_attr($args['type']); ?>"
            <?php if (!empty($args['id'])): ?>id="<?php echo esc_attr($args['id']); ?>"<?php endif; ?>
            <?php if (!empty($args['name'])): ?>name="<?php echo esc_attr($args['name']); ?>"<?php endif; ?>
            class="<?php echo esc_attr(implode(' ', $classes)); ?>"
            <?php disabled($args['disabled']); ?>
        >
            <?php echo esc_html($args['text']); ?>
        </button>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render an info box
     * 
     * @param array $args Info box arguments
     * @return string HTML output
     */
    public static function render_info_box($args = array()) {
        $defaults = array(
            'title' => '',
            'content' => '',
            'type' => 'info', // info, success, warning, error
            'icon' => true
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $icons = array(
            'info' => '💡',
            'success' => '✅',
            'warning' => '⚠️',
            'error' => '❌'
        );
        
        ob_start();
        ?>
        <div class="sohoj-info-box sohoj-info-box--<?php echo esc_attr($args['type']); ?>">
            <?php if ($args['icon'] && isset($icons[$args['type']])): ?>
                <span class="sohoj-info-box__icon"><?php echo $icons[$args['type']]; ?></span>
            <?php endif; ?>
            
            <div class="sohoj-info-box__content">
                <?php if (!empty($args['title'])): ?>
                    <h4 class="sohoj-info-box__title"><?php echo esc_html($args['title']); ?></h4>
                <?php endif; ?>
                
                <div class="sohoj-info-box__text">
                    <?php echo wp_kses_post($args['content']); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render a creative text input field
     * 
     * @param array $args Input arguments
     * @return string HTML output
     */
    public static function render_text_input($args = array()) {
        $defaults = array(
            'id' => '',
            'name' => '',
            'value' => '',
            'label' => '',
            'info' => '',
            'placeholder' => '',
            'class' => '',
            'type' => 'text',
            'icon' => '',
            'required' => false
        );
        
        $args = wp_parse_args($args, $defaults);
        
        ob_start();
        ?>
        <div class="sohoj-creative-field">
            <?php if (!empty($args['label'])): ?>
                <label for="<?php echo esc_attr($args['id']); ?>" class="sohoj-creative-label">
                    <?php if (!empty($args['icon'])): ?>
                        <span class="label-icon"><?php echo wp_kses_post($args['icon']); ?></span>
                    <?php endif; ?>
                    <span class="label-text"><?php echo esc_html($args['label']); ?></span>
                    <?php if ($args['required']): ?>
                        <span class="required-star">*</span>
                    <?php endif; ?>
                </label>
            <?php endif; ?>
            
            <div class="sohoj-input-wrapper">
                <input 
                    type="<?php echo esc_attr($args['type']); ?>"
                    id="<?php echo esc_attr($args['id']); ?>" 
                    name="<?php echo esc_attr($args['name']); ?>" 
                    value="<?php echo esc_attr($args['value']); ?>"
                    placeholder="<?php echo esc_attr($args['placeholder']); ?>"
                    class="sohoj-creative-input <?php echo esc_attr($args['class']); ?>"
                    <?php if ($args['required']): ?>required<?php endif; ?>
                />
                <div class="input-focus-ring"></div>
            </div>
            
            <?php if (!empty($args['info'])): ?>
                <div class="sohoj-field-info">
                    <div class="info-icon">💡</div>
                    <div class="info-text"><?php echo wp_kses_post($args['info']); ?></div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render a creative textarea field
     * 
     * @param array $args Textarea arguments
     * @return string HTML output
     */
    public static function render_textarea($args = array()) {
        $defaults = array(
            'id' => '',
            'name' => '',
            'value' => '',
            'label' => '',
            'info' => '',
            'placeholder' => '',
            'class' => '',
            'rows' => 4,
            'icon' => '',
            'required' => false
        );
        
        $args = wp_parse_args($args, $defaults);
        
        ob_start();
        ?>
        <div class="sohoj-creative-field">
            <?php if (!empty($args['label'])): ?>
                <label for="<?php echo esc_attr($args['id']); ?>" class="sohoj-creative-label">
                    <?php if (!empty($args['icon'])): ?>
                        <span class="label-icon"><?php echo wp_kses_post($args['icon']); ?></span>
                    <?php endif; ?>
                    <span class="label-text"><?php echo esc_html($args['label']); ?></span>
                    <?php if ($args['required']): ?>
                        <span class="required-star">*</span>
                    <?php endif; ?>
                </label>
            <?php endif; ?>
            
            <div class="sohoj-textarea-wrapper">
                <textarea 
                    id="<?php echo esc_attr($args['id']); ?>" 
                    name="<?php echo esc_attr($args['name']); ?>" 
                    placeholder="<?php echo esc_attr($args['placeholder']); ?>"
                    class="sohoj-creative-textarea <?php echo esc_attr($args['class']); ?>"
                    rows="<?php echo esc_attr($args['rows']); ?>"
                    <?php if ($args['required']): ?>required<?php endif; ?>
                ><?php echo esc_textarea($args['value']); ?></textarea>
                <div class="textarea-focus-ring"></div>
            </div>
            
            <?php if (!empty($args['info'])): ?>
                <div class="sohoj-field-info">
                    <div class="info-icon">💡</div>
                    <div class="info-text"><?php echo wp_kses_post($args['info']); ?></div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render a creative select dropdown
     * 
     * @param array $args Select arguments
     * @return string HTML output
     */
    public static function render_select($args = array()) {
        $defaults = array(
            'id' => '',
            'name' => '',
            'value' => '',
            'label' => '',
            'info' => '',
            'options' => array(),
            'class' => '',
            'icon' => '',
            'required' => false
        );
        
        $args = wp_parse_args($args, $defaults);
        
        ob_start();
        ?>
        <div class="sohoj-creative-field">
            <?php if (!empty($args['label'])): ?>
                <label for="<?php echo esc_attr($args['id']); ?>" class="sohoj-creative-label">
                    <?php if (!empty($args['icon'])): ?>
                        <span class="label-icon"><?php echo wp_kses_post($args['icon']); ?></span>
                    <?php endif; ?>
                    <span class="label-text"><?php echo esc_html($args['label']); ?></span>
                    <?php if ($args['required']): ?>
                        <span class="required-star">*</span>
                    <?php endif; ?>
                </label>
            <?php endif; ?>
            
            <div class="sohoj-select-wrapper">
                <select 
                    id="<?php echo esc_attr($args['id']); ?>" 
                    name="<?php echo esc_attr($args['name']); ?>" 
                    class="sohoj-creative-select <?php echo esc_attr($args['class']); ?>"
                    <?php if ($args['required']): ?>required<?php endif; ?>
                >
                    <?php foreach ($args['options'] as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($args['value'], $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="select-focus-ring"></div>
                <div class="select-arrow">
                    <svg width="12" height="8" viewBox="0 0 12 8" fill="none">
                        <path d="M1 1.5L6 6.5L11 1.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            
            <?php if (!empty($args['info'])): ?>
                <div class="sohoj-field-info">
                    <div class="info-icon">💡</div>
                    <div class="info-text"><?php echo wp_kses_post($args['info']); ?></div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render form container
     * 
     * @param string $content Form content
     * @param array $args Container arguments
     * @return string HTML output
     */
    public static function render_form_container($content, $args = array()) {
        $defaults = array(
            'title' => '',
            'description' => '',
            'class' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        ob_start();
        ?>
        <div class="sohoj-form-container <?php echo esc_attr($args['class']); ?>">
            <?php if (!empty($args['title'])): ?>
                <div class="sohoj-form-header">
                    <h2 class="sohoj-form-title"><?php echo esc_html($args['title']); ?></h2>
                    <?php if (!empty($args['description'])): ?>
                        <p class="sohoj-form-description"><?php echo wp_kses_post($args['description']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="sohoj-form-body">
                <?php echo $content; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}