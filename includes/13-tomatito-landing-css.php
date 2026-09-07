<?php

add_action( 'wp_head', function() {

    if ( ! is_front_page() ) {
        return;
    }

    ?>
    <style>
    /* ========================= */
    /* LANDING PAGE              */
    /* ========================= */

    /* RESET */
    body {
        background: #f5f6f8 !important;
        margin: 0 !important;
    }

    #wpadminbar { display: none !important; }

    header,
    .wp-block-template-part,
    .site-header {
        display: none !important;
    }

    .wp-site-blocks,
   .wp-block-post-content,
   main,
   .entry-content {
       max-width: 100% !important;
       margin: 0 !important;
       padding: 0 20px !important; 
   }

    .entry-title {
        display: none;
    }

    /* HERO */
    .hero-section {
        padding: 160px 40px 80px !important;
// 		padding-left: 60px !important;
//         padding-bottom: 80px !important;
    }

    .hero-section .wp-block-columns {
        align-items: center;
        gap: 40px;
    }

    .hero-section h1 {
        font-size: 52px;
        line-height: 1.2;
        margin-bottom: 20px;
    }

    .hero-section p {
        font-size: 18px;
        color: #555;
        margin-bottom: 25px;
    }

    /* BOTÃO HERO */
    .wp-block-button__link {
        background: linear-gradient(135deg, #ff4d4d, #ff6b6b);
        color: white !important;
        border-radius: 999px;
        padding: 14px 32px;
        font-weight: 600;
        border: none;
        transition: all 0.3s ease;
    }

    .wp-block-button__link:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(255, 77, 77, 0.4);
    }

    /* BENEFÍCIOS / COLUNAS */
    .wp-block-columns {
        gap: 30px !important;
        margin-top: 60px;
        justify-content: center;
    }

    .wp-block-columns .wp-block-column {
        background: #ffffff;
        padding: 30px 25px;
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        min-width: 260px;
        max-width: 320px;
    }

    .wp-block-columns .wp-block-column:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.08);
    }

    .wp-block-columns h2 {
        font-size: 24px;
        margin-bottom: 12px;
        line-height: 1.3;
        word-break: keep-all;
        overflow-wrap: break-word;
    }

    .wp-block-columns p {
        font-size: 15px;
        color: #666;
        line-height: 1.6;
    }

    /* SECÇÕES */
    .benefits-section {
        margin-top: 80px !important;
    }

    .steps-section {
        margin-top: 100px !important;
    }

    .benefits-section h2,
    .steps-section h2 {
        margin-bottom: 30px !important;
    }

    .benefits-section .wp-block-columns,
    .steps-section .wp-block-columns {
        margin-top: 20px !important;
    }

    /* RESPONSIVO */
    @media (max-width: 768px) {
		.wp-site-blocks,
		.wp-block-post-content,
		main,
		.entry-content {
			padding: 0 20px !important;
		}
		.hero-section {
            padding: 120px 20px 60px !important;
        }
        .hero-section h1 {
            font-size: 36px;
        }

        .wp-block-columns {
            flex-direction: column;
            align-items: center;
        }

        .wp-block-columns .wp-block-column {
            width: 100%;
            max-width: 100%;
        }
    }

    </style>
    <?php
});
