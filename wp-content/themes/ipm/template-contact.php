<?php
/*
Template Name: Contact Page
*/
get_header() ?>

<main class="contact">
    <?php
    $image = get_field('image');
    $title = get_field('title');

    if ($image || $title): ?>
        <div class="banner">
            <?php if ($image): ?>
                <img src="<?php echo esc_url($image); ?>" alt="" class="w-full">
            <?php endif; ?>

            <?php if ($title): ?>
                <div class="container">
                    <h1 class="title-lg"><?php echo esc_html($title); ?></h1>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="contact-form">
        <div class="container">
            <div class="contact-form__wrapper">
                <div class="contact-form__left">
                    <div class="title-md text-purple"><?php echo get_field('contact_title'); ?></div>
                    <div class="name">ADDRESS</div>
                    <div class="text"><?php echo get_field('contact_address'); ?></div>
                    <div class="map">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path
                                d="M19.527 4.79902C20.739 7.40702 20.464 10.477 19.122 12.972C18.021 15.019 16.378 16.712 15.024 18.586C14.405 19.444 13.78 20.336 13.3549 21.313C13.214 21.638 13.092 21.971 12.972 22.305C12.851 22.638 12.748 22.978 12.632 23.313C12.523 23.627 12.396 23.997 12.005 24H11.998C11.532 23.999 11.419 23.47 11.303 23.113C11.019 22.239 10.722 21.4 10.284 20.588C9.77395 19.644 9.13895 18.771 8.49395 17.917L19.527 4.79902ZM8.54495 7.70502L4.58595 12.412C5.30995 13.952 6.40695 15.275 7.45695 16.592C7.70428 16.902 7.94995 17.214 8.19395 17.528L13.178 11.603L13.149 11.613C11.408 12.214 9.45795 11.322 8.75695 9.62602C8.66124 9.39574 8.59115 9.15563 8.54795 8.91002C8.48495 8.47302 8.47095 8.14902 8.54395 7.71202L8.54495 7.70502ZM5.49195 3.14902L5.48895 3.15302C3.54195 5.61902 3.20795 9.03302 4.37195 11.923L9.15695 6.23402L9.09895 6.18402L5.49195 3.14902ZM14.661 0.436019L10.8229 4.99902L10.85 4.98902C12.45 4.43802 14.2529 5.13902 15.07 6.61502C15.2459 6.93402 15.393 7.29802 15.4469 7.66002C15.5149 8.10602 15.5319 8.43302 15.459 8.88002L15.456 8.89602L19.292 4.33502C18.2897 2.50654 16.6417 1.11742 14.67 0.439019L14.661 0.436019ZM9.46595 5.86802L14.162 0.285019L14.115 0.273019C13.4201 0.0902536 12.7044 -0.00151361 11.986 1.88801e-05C10.8226 0.0109758 9.674 0.262344 8.61239 0.738339C7.55078 1.21433 6.59905 1.90468 5.81695 2.76602L5.80095 2.78402L9.46595 5.86802Z"
                                fill="black" />
                        </svg>
                        <a href="https://maps.app.goo.gl/jVFvNsZzMMVFhMNi8" target="_blank">Open on Google Maps</a>
                    </div>
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2956.0452720088415!2d-6.246731323268855!3d53.33956007228736!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x48670e8d1bdaf2ed%3A0x76b7c42815fd64d6!2sIrish%20Institute%20of%20Pensions%20Management!5e1!3m2!1suk!2sua!4v1741366111827!5m2!1suk!2sua"
                        width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"></iframe>
                    <div class="name">E-MAIL ADDRESS</div>
                    <?php
                    $link = get_field('email');
                    if ($link):
                        $link_url = $link['url'];
                        $link_title = $link['title'];
                        $link_target = $link['target'] ? $link['target'] : '_self';
                        ?>
                        <a class="text" href="<?php echo esc_url($link_url); ?>"
                            target="<?php echo esc_attr($link_target); ?>"><?php echo esc_html($link_title); ?></a>
                    <?php endif; ?>

                    <div class="name">TELEPHONE</div>
                    <?php
                    if (have_rows('contact_links')):
                        while (have_rows('contact_links')):
                            the_row();
                            ?>
                            <?php
                            $link = get_sub_field('link');
                            if ($link):
                                $link_url = $link['url'];
                                $link_title = $link['title'];
                                $link_target = $link['target'] ? $link['target'] : '_self';
                                ?>
                                <a class="text" href="<?php echo esc_url($link_url); ?>"
                                    target="<?php echo esc_attr($link_target); ?>"><?php echo esc_html($link_title); ?></a>
                            <?php endif; ?>
                        <?php endwhile;
                    else:
                    endif;
                    ?>

                    <div class="name">SOCIAL MEDIA</div>
                    <div class="contact-form__social">
                        <?php
                        if (have_rows('social')):
                            while (have_rows('social')):
                                the_row();
                                ?>
                                <?php
                                $link = get_sub_field('link');
                                if ($link): ?>
                                    <a href="<?php echo esc_url($link); ?>">
                                        <?php
                                        $image = get_sub_field('image');
                                        if ($image): ?>
                                            <img src="<?php echo esc_url($image); ?>" alt="">
                                        <?php endif; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endwhile;
                        else:
                        endif;
                        ?>
                    </div>

                </div>
                <div class="contact-form__right">
                    <div class="text-40 text-purple"><?php echo get_field('form_title'); ?></div>
                    <div class="text"><?php echo get_field('form_text'); ?></div>
                    <?php echo do_shortcode('[contact-form-7 id="6a4574d" title="Contact"]'); ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php get_footer() ?>