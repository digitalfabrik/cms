<?php

function integreat_customizer( $wp_customize ) {
    // web view section
    $wp_customize->add_section(
        'webview',
        array(
            'title'     => 'Web View',
            'priority'  => 20
        )
    );

    $wp_customize->add_setting(
        'webview_contentheader_img',
        array(
            'default'      => '',
            'transport'    => 'postMessage'
        )
    );
    $wp_customize->add_control(
        new WP_Customize_Cropped_Image_Control(
            $wp_customize,
            'webview_contentheader_img',
            array(
                'section'     => 'webview',
                'label'       => __( 'Header Image', 'integreat' ),
                'width'       => 1920,
                'height'      => 900,
            )
        )
    );

    $wp_customize->add_setting(
        'webview_google_analytics_code',
        array(
            'default'      => '',
            'transport'    => 'postMessage'
        )
    );
    $wp_customize->add_control(
        'webview_google_analytics_code',
        array(
            'section'     => 'webview',
            'label'       => 'Google Analytics Code',
            'type'        => 'textarea'
        )
    );

    // imprint-, privacy- and contact-page
    $wp_customize->add_section(
        'imprintsites',
        array(
            'title'     => __( 'Imprint, Privacy and Contact', 'integreat' ),
            'priority'  => 300
        )
    );

    // imprint site
    $wp_customize->add_setting(
        'imprintsites_imprint',
        array(
            'default'      => '',
            'transport'    => 'postMessage'
        )
    );
    $wp_customize->add_control(
        'imprintsites_imprint',
        array(
            'section'     => 'imprintsites',
            'label'       => __( 'Imprint', 'integreat' ),
            'type'        => 'dropdown-pages'
        )
    );

    // privacy site
    $wp_customize->add_setting(
        'imprintsites_privacy',
        array(
            'default'      => '',
            'transport'    => 'postMessage'
        )
    );
    $wp_customize->add_control(
        'imprintsites_privacy',
        array(
            'section'     => 'imprintsites',
            'label'       => __( 'Privacy', 'integreat' ),
            'type'        => 'dropdown-pages'
        )
    );

    // contact link
    $wp_customize->add_setting(
        'imprintsites_contact',
        array(
            'default'      => '',
            'transport'    => 'postMessage'
        )
    );
    $wp_customize->add_control(
        new WP_Customize_Control(
            $wp_customize,
            'imprintsites_contact',
            array(
                'section'   => 'imprintsites',
                'label'     => __( 'Contact link', 'integreat' ),
            )
        )
    );
}
add_action('customize_register', 'integreat_customizer' );

?>