<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package IPM
 */

?>

<footer class="footer">
    <div class="container">
        <div class="footer__wrapper">
            <div class="footer__left">
                <div class="footer__logo">
                    <a href="<?php echo home_url(); ?>">
                        <?php
                        $image = get_field('footer_logo', 'option');

                        if ($image) {
                            echo '<img src="' . esc_url($image) . '" alt="logo" style="width:164px; height:auto; display:block;">';
                        }
                        ?>
                    </a>
                </div>
                <div class="footer__menu">
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'footer-menu',
                        'container' => false,
                        'menu_class' => '',
                        'depth' => 1,
                    ));
                    ?>
                </div>
            </div>
            <div class="footer__contacts">
                <div class="footer__address">
                    <?php echo get_field('footer_address', 'option'); ?>
                </div>
                <?php
                if (have_rows('footer_links', 'option')):
                    while (have_rows('footer_links', 'option')):
                        the_row();
                        ?>
                        <?php
                        $link = get_sub_field('link');
                        if ($link):
                            $link_url = $link['url'];
                            $link_title = $link['title'];
                            $link_target = $link['target'] ? $link['target'] : '_self';
                            ?>
                            <a href="<?php echo esc_url($link_url); ?>"
                                target="<?php echo esc_attr($link_target); ?>"><?php echo esc_html($link_title); ?></a>
                        <?php endif; ?>
                    <?php endwhile;
                else:
                endif;
                ?>
                <div class="footer__social">
                    <?php
                    if (have_rows('footer_social', 'option')):
                        while (have_rows('footer_social', 'option')):
                            the_row();
                            ?>
                            <?php
                            $link = get_sub_field('link');
                            if ($link):
                                $link_url = $link['url'];
                                $link_target = $link['target'] ? $link['target'] : '_self';
                                ?>
                                <a href="<?php echo esc_url($link_url); ?>" target="<?php echo esc_attr($link_target); ?>">
                                    <?php
                                    $image = get_sub_field('image');
                                    if ($image): ?>
                                        <img width="30" src="<?php echo esc_url($image); ?>" alt="">
                                    <?php endif; ?></a>
                            <?php endif; ?>
                        <?php endwhile;
                    else:
                    endif;
                    ?>
                </div>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<?php wp_footer(); ?>

<?php
// Include notification system
include get_template_directory() . '/includes/notification-system.php';
?>

</body>

</html>