<?php
/*
Template Name: Service Page
*/
get_header() ?>

<main class="membership-detail">
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
    <div class="membership-content">

        <div class="container">
            <?php
            $link = get_field('link');
            if ($link):
                $link_url = $link['url'];
                $link_title = $link['title'];
                $link_target = $link['target'] ? $link['target'] : '_self';
                ?>
                <a class="button" href="<?php echo esc_url($link_url); ?>" target="<?php echo esc_attr($link_target); ?>">

                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="23" viewBox="0 0 30 23" fill="none">
                        <path
                            d="M0.93934 10.4393C0.353554 11.0251 0.353554 11.9749 0.939341 12.5607L10.4853 22.1066C11.0711 22.6924 12.0208 22.6924 12.6066 22.1066C13.1924 21.5208 13.1924 20.5711 12.6066 19.9853L4.12132 11.5L12.6066 3.01472C13.1924 2.42893 13.1924 1.47919 12.6066 0.8934C12.0208 0.307613 11.0711 0.307613 10.4853 0.8934L0.93934 10.4393ZM30 10L2 10L2 13L30 13L30 10Z"
                            fill="#724491" />
                    </svg>
                    <span><?php echo esc_html($link_title); ?></span>
                </a>
            <?php endif; ?>

            <div class="membership-content__wrapper">
                <div class="membership-content__left">
                    <div class="title-md"><?php echo get_field('membership_title'); ?></div>
                    <div class="text"><?php echo get_field('membership_text'); ?></div>
                    <div class="links">
                        <div class="text-40"><?php echo get_field('links_title'); ?></div>
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
                <div class="membership-content__right">
                    <?php
                    if (have_rows('membership')):
                        while (have_rows('membership')):
                            the_row();
                            ?>
                            <div class="name"><?php echo get_sub_field('title'); ?></div>
                            <div class="text"><?php echo get_sub_field('text'); ?></div>
                        <?php endwhile;
                    else:
                    endif;
                    ?>

                </div>
            </div>
        </div>
    </div>
    </div>
</main>


<?php get_footer() ?>