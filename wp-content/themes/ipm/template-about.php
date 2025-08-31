<?php
/*
Template Name: About Us Page
*/
get_header() ?>


<script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js" integrity="sha512-uURl+ZXMBrF4AwGaWmEetzrd+J5/8NRkWAvJx5sbPSSuOb0bZLqf+tOzniObO00BjHa/dD7gub9oCGMLPQHtQA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.css" integrity="sha512-nNlU0WK2QfKsuEmdcTwkeh+lhGs6uyOxuUs+n+0oXSYDok5qy0EI0lt01ZynHq6+p/tbgpZ7P+yUb+r71wqdXg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<main class="about">
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
    <div class="about-text">
        <div class="container">
            <div class="about-text__wrapper">
                <div class="about-text__left ">
                    <?php
                    if (have_rows('block_repeater')):
                        while (have_rows('block_repeater')):
                            the_row();
                            ?>
                            <div class="text"><?php echo get_sub_field('text'); ?></div>
                        <?php endwhile;
                    else:
                    endif;
                    ?>
                </div>
                <div class="about-text__right">
                    <?php
                    $image = get_field('block_image');
                    if ($image): ?>
                        <img src="<?php echo esc_url($image); ?>" alt="">
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="members">
        <div class="container">
            <div class="title-md"><?php echo get_field('members_title'); ?></div>
            <div class="members__items">
                <?php
                if (have_rows('members')):
                    while (have_rows('members')):
                        the_row();
                        ?>
                        <div class="members__item">
                            <?php
                            $image = get_sub_field('image');

                            if ($image) {
                                echo '<img src="' . esc_url($image) . '" alt="">';
                            }
                            ?>
                            <div class="title-xs"><?php echo get_sub_field('name'); ?>
                                <?php
                                $link = get_sub_field('link');
                                if ($link): ?>
                                    <a target="_blank" href="<?php echo esc_url($link); ?>"><svg xmlns="http://www.w3.org/2000/svg" width="28"
                                            height="28" viewBox="0 0 28 28" fill="none">
                                            <path
                                                d="M22.1667 3.5C22.7855 3.5 23.379 3.74583 23.8166 4.18342C24.2542 4.621 24.5 5.21449 24.5 5.83333V22.1667C24.5 22.7855 24.2542 23.379 23.8166 23.8166C23.379 24.2542 22.7855 24.5 22.1667 24.5H5.83333C5.21449 24.5 4.621 24.2542 4.18342 23.8166C3.74583 23.379 3.5 22.7855 3.5 22.1667V5.83333C3.5 5.21449 3.74583 4.621 4.18342 4.18342C4.621 3.74583 5.21449 3.5 5.83333 3.5H22.1667ZM21.5833 21.5833V15.4C21.5833 14.3913 21.1826 13.4239 20.4694 12.7106C19.7561 11.9974 18.7887 11.5967 17.78 11.5967C16.7883 11.5967 15.6333 12.2033 15.0733 13.1133V11.8183H11.8183V21.5833H15.0733V15.8317C15.0733 14.9333 15.7967 14.1983 16.695 14.1983C17.1282 14.1983 17.5436 14.3704 17.8499 14.6767C18.1562 14.983 18.3283 15.3985 18.3283 15.8317V21.5833H21.5833ZM8.02667 9.98667C8.54649 9.98667 9.04502 9.78017 9.4126 9.4126C9.78017 9.04502 9.98667 8.54649 9.98667 8.02667C9.98667 6.94167 9.11167 6.055 8.02667 6.055C7.50375 6.055 7.00225 6.26273 6.63249 6.63249C6.26273 7.00225 6.055 7.50375 6.055 8.02667C6.055 9.11167 6.94167 9.98667 8.02667 9.98667ZM9.64833 21.5833V11.8183H6.41667V21.5833H9.64833Z"
                                                fill="#6B6B6B" />
                                        </svg></a>
                                <?php endif; ?>
                            </div>
                            <div class="text-18"><?php echo get_sub_field('position'); ?></div>
                        </div>

                    <?php endwhile;
                else:
                endif;
                ?>
            </div>
        </div>
    </div>
    <div class="presidents">
        <div class="container">
            <div class="title-md"><?php echo get_field('presidents_title'); ?></div>
            <div class="presidents__wrapper">
                <div class="presidents__items">
                    <?php
                    if (have_rows('presidents')):
                        while (have_rows('presidents')):
                            the_row();
                            ?>
                            <div class="presidents__item">
                                <div class="text-18"><?php echo get_sub_field('year'); ?></div>
                                <div class="text-24"><?php echo get_sub_field('name'); ?></div>
                            </div>
                        <?php endwhile;
                    else:
                    endif;
                    ?>

                </div>

            </div>
        </div>
    </div>
    <div class="code">
        <div class="container">
            <div class="title-md"><?php echo get_field('code_title'); ?></div>
            <div class="text"><?php echo get_field('code_text'); ?></div>
         
                <a class="button" href="<?php echo get_field('code_button_link') ?>"
                   ><?php echo get_field('code_button_text') ?><svg width="30"
                        height="23" viewBox="0 0 30 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M29.0607 12.5607C29.6464 11.9749 29.6464 11.0251 29.0607 10.4393L19.5147 0.893398C18.9289 0.307611 17.9792 0.307611 17.3934 0.893398C16.8076 1.47919 16.8076 2.42893 17.3934 3.01472L25.8787 11.5L17.3934 19.9853C16.8076 20.5711 16.8076 21.5208 17.3934 22.1066C17.9792 22.6924 18.9289 22.6924 19.5147 22.1066L29.0607 12.5607ZM0 13H28V10H0V13Z"
                            fill="#724491" />
                    </svg></a>
        </div>
    </div>
    <div class="gallery">
        <div class="container">
            <div class="title-md"><?php echo get_field('gallery_title'); ?></div>
            <div class="gallerySwiper swiper">
                <div class="swiper-wrapper ">
                    <?php
                    if (have_rows('gallery_images')):
                        while (have_rows('gallery_images')):
                            the_row();
                            ?>
                            <div class="swiper-slide">
                                <?php
                                $image = get_sub_field('image');

                                if ($image) {
                                    echo '<a data-fancybox href="' . esc_url($image) . '"><img src="' . esc_url($image) . '" alt=""></a>';
                                }
                                ?>
                            </div>

                        <?php endwhile;
                    else:
                    endif;
                    ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php get_footer() ?>