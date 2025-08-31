<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package IPM
 */

get_header();
?>

<main class="single-news">
    <div class="banner">
        <?php
        $image = get_field('image');
        if ($image): ?>
            <img src="<?php echo esc_url($image); ?>" alt="" class="w-full">
		<?php else:?>
		<img src="https://iipm.ie/wp-content/uploads/2025/03/newsroom.png" alt="" class="w-full">
		
        <?php endif; ?>

        <div class="container">
            <h1 class="title-lg"><?php echo get_field('title'); ?></h1>
        </div>
    </div>
    <div class="single-content">
        <div class="container">
            <a class="button" href="#" onclick="goBackOrRedirect(event)">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="23" viewBox="0 0 30 23" fill="none">
                    <path
                        d="M0.93934 10.4393C0.353554 11.0251 0.353554 11.9749 0.939341 12.5607L10.4853 22.1066C11.0711 22.6924 12.0208 22.6924 12.6066 22.1066C13.1924 21.5208 13.1924 20.5711 12.6066 19.9853L4.12132 11.5L12.6066 3.01472C13.1924 2.42893 13.1924 1.47919 12.6066 0.8934C12.0208 0.307613 11.0711 0.307613 10.4853 0.8934L0.93934 10.4393ZM30 10L2 10L2 13L30 13L30 10Z"
                        fill="#724491" />
                </svg>
                <span>Back to newsroom</span>
            </a>

            <div class="single-content__block">
                <div class="single-content__top">
                    <div class="text-18"><?php echo get_the_category()[0]->name; ?></div>
                    <div class="text-18"><?php echo get_the_date(); ?></div>
                </div>
                <div class="content">
                    <div class="title-md"><?php the_title(); ?></div>
                   <?php the_content();?>
                </div>
            </div>
        </div>
    </div>

    <div class="news-archive">
        <div class="container">
            <div class="text-40">See more articles</div>
            <div class="news-archive__items">
                <?php
                $recent_posts = new WP_Query([
                    'post_type' => 'post',
                    'posts_per_page' => 4,
                    'post__not_in' => [get_the_ID()]
                ]);

                while ($recent_posts->have_posts()):
                    $recent_posts->the_post();
                    ?>
                    <div class="news-archive__item">
                        <div class="news-archive__top">
                            <div class="text-18"><?php echo get_the_category()[0]->name; ?></div>
                            <div class="text-18"><?php echo get_the_date(); ?></div>
                        </div>
                        <div class="text-30"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></div>
                    </div>
                <?php endwhile;
                wp_reset_postdata(); ?>
            </div>
        </div>
    </div>
</main>


<script>
    function goBackOrRedirect(event) {
        event.preventDefault();

        const referrer = document.referrer;
        const currentHost = window.location.hostname;

        if (referrer) {
            window.history.back();
        } else {
          
            window.location.href = "/blog/";
        }
    }
</script>

<?php
get_footer();
