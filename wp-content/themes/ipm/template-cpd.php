<?php
/*
Template Name: CPD Page
*/
get_header() ?>

<main class="cpd">
    <div class="banner">
        <?php
        $image = get_field('image');
        if ($image): ?>
            <img src="<?php echo esc_url($image); ?>" alt="" class="w-full">
        <?php endif; ?>

        <div class="container">
            <h1 class="title-lg"><?php echo get_field('title'); ?></h1>
        </div>
    </div>
    <div class="cpd-text">
        <div class="container">
            <div class="cpd-text__left">
                <div class="title-md text-orange"><?php echo get_field('block_title'); ?>
                </div>
            </div>
            <div class="cpd-text__right">
                <?php
                if (have_rows('block_content')):
                    while (have_rows('block_content')):
                        the_row();
                        ?>
                        <div class="text"><?php echo get_sub_field('text'); ?></div>
                    <?php endwhile;
                else:
                endif;
                ?>
            </div>
        </div>
    </div>
    <div class="sign">
        <div class="container">
            <div class="sign__wrapper">
                <div class="sign__left">
                    <?php
                    $image = get_field('sign_image');
                    if ($image): ?>
                        <img src="<?php echo esc_url($image); ?>" alt="">
                    <?php endif; ?>
                </div>
                <div class="sign__right">
                    <div class="title-md text-purple"><?php echo get_field('sign_title'); ?></div>
                    <div class="text"><?php echo get_field('sign_text'); ?></div>
                    <div class="links">
                        <div class="text-30"><?php echo get_field('sign_links_title'); ?></div>
                        <?php
                        if (have_rows('sign_repeater')):
                            while (have_rows('sign_repeater')):
                                the_row();
                                ?>
                                <?php
                                $link = get_sub_field('link');
                                if ($link):
                                    $link_url = $link['url'];
                                    $link_title = $link['title'];
                                    $link_target = $link['target'] ? $link['target'] : '_self';
                                    ?>
                                    <a class="button" href="<?php echo esc_url($link_url); ?>"
                                        target="<?php echo esc_attr($link_target); ?>">

                                        <span><?php echo esc_html($link_title); ?></span>
                                        <svg width="30" height="23" viewBox="0 0 30 23" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M29.0607 12.5607C29.6464 11.9749 29.6464 11.0251 29.0607 10.4393L19.5147 0.893398C18.9289 0.307611 17.9792 0.307611 17.3934 0.893398C16.8076 1.47919 16.8076 2.42893 17.3934 3.01472L25.8787 11.5L17.3934 19.9853C16.8076 20.5711 16.8076 21.5208 17.3934 22.1066C17.9792 22.6924 18.9289 22.6924 19.5147 22.1066L29.0607 12.5607ZM0 13H28V10H0V13Z"
                                                fill="#724491" />
                                        </svg>
                                    </a>
                                <?php endif; ?>
                            <?php endwhile;
                        else:
                        endif;
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="organizations">
        <div class="container">
            <div class="title-md text-purple"><?php echo get_field('organizations_title'); ?></div>
            <div class="text"><?php echo get_field('organizations_text'); ?></div>
            <div class="organizations__images">
                <?php
                if (have_rows('organizations_images')):
                    while (have_rows('organizations_images')):
                        the_row();
                        ?>
                        <?php
                        $image = get_sub_field('image');

                        if ($image) {
                            echo '<img src="' . esc_url($image) . '" alt="">';
                        }
                        ?>
                    <?php endwhile;
                else:
                endif;
                ?>
            </div>
        </div>
    </div>
    <div class="help">
        <div class="container">
            <?php
            if (have_rows('help_repeater')):
                while (have_rows('help_repeater')):
                    the_row();
                    ?>
                    <div class="links">
                        <div class="text-40"><?php echo get_sub_field('title'); ?></div>
                        <?php
                        if (have_rows('links')):
                            while (have_rows('links')):
                                the_row();
                                ?>
                                <?php
                                $link = get_sub_field('link');
                                if ($link):
                                    $link_url = $link['url'];
                                    $link_title = $link['title'];
                                    $link_target = $link['target'] ? $link['target'] : '_self';
                                    ?>
                                    <a class="button" href="<?php echo esc_url($link_url); ?>"
                                        target="<?php echo esc_attr($link_target); ?>">

                                        <span><?php echo esc_html($link_title); ?></span>
                                        <svg width="30" height="23" viewBox="0 0 30 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M29.0607 12.5607C29.6464 11.9749 29.6464 11.0251 29.0607 10.4393L19.5147 0.893398C18.9289 0.307611 17.9792 0.307611 17.3934 0.893398C16.8076 1.47919 16.8076 2.42893 17.3934 3.01472L25.8787 11.5L17.3934 19.9853C16.8076 20.5711 16.8076 21.5208 17.3934 22.1066C17.9792 22.6924 18.9289 22.6924 19.5147 22.1066L29.0607 12.5607ZM0 13H28V10H0V13Z"
                                                fill="#724491" />
                                        </svg>
                                    </a>
                                <?php endif; ?>
                            <?php endwhile;
                        else:
                        endif;
                        ?>
                    </div>
                <?php endwhile;
            else:
            endif;
            ?>

        </div>
    </div>
</main>

<?php get_footer() ?>