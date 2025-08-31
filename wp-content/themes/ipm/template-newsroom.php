<?php
/*
Template Name: Newsroom Page
*/
get_header() ?>


<main class="newsroom">
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
    <div class="news">
        <div class="container">
            <div class="news__items">
                <?php
                $args = array(
                    'post_type' => 'post', 
                    'posts_per_page' => 8, 
                    'orderby' => 'date', 
                    'order' => 'DESC',
                );

                $query = new WP_Query($args);

                if ($query->have_posts()):
                    while ($query->have_posts()):
                        $query->the_post();
                        ?>
                        <a class="news__item" href="<?php the_permalink(); ?>">
                            <?php if (has_post_thumbnail()): ?>
                                <img src="<?php the_post_thumbnail_url('full'); ?>" alt="<?php the_title(); ?>">
                            <?php endif; ?>
                            <div class="news__top">
                                <div class="text-18"><?php echo get_the_category()[0]->name; ?></div>
                                <div class="text-18"><?php the_time('F j, Y'); ?></div>
                            </div>
                            <div class="text-40"><?php the_title(); ?></div>
                            <div class="text"><?php echo wp_trim_words(get_the_excerpt(), 30, '...'); ?></div>
                        </a>
                        <?php
                    endwhile;
                    wp_reset_postdata(); 
                else:
                    ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="news-archive">
        <div class="container">
            <div class="text-40">Seminar archive</div>
            <div class="news-archive__items">
                <?php

                $args = array(
                    'post_type' => 'post',
                    'posts_per_page' => 8,
                    'orderby' => 'date',
                    'order' => 'DESC',
                );

                $archive_query = new WP_Query($args);

                if ($archive_query->have_posts()):
                    while ($archive_query->have_posts()):
                        $archive_query->the_post();
                        ?>
                        <a class="news-archive__item" href="<?php the_permalink(); ?>">
                            
                            <div class="news-archive__top">
                                <div class="text-18"><?php echo get_the_category()[0]->name; ?></div>
                                <div class="text-18"><?php the_time('F j, Y'); ?></div>
                            </div>
                            <div class="text-30"><?php the_title(); ?></div> 
                        </a>
                        <?php
                    endwhile;
                    wp_reset_postdata();
                else:
                    ?>

                <?php endif; ?>
            </div>
            <?php
            $link = get_field('newsroom_link');
            if ($link):
                $link_url = $link['url'];
                $link_title = $link['title'];
                $link_target = $link['target'] ? $link['target'] : '_self';
                ?>
                <a class="see-all" href="<?php echo esc_url($link_url); ?>"
                    target="<?php echo esc_attr($link_target); ?>"><?php echo esc_html($link_title); ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="25" height="15" viewBox="0 0 25 15" fill="none">
                        <path
                            d="M24.7071 8.20711C25.0976 7.81658 25.0976 7.18342 24.7071 6.79289L18.3431 0.428932C17.9526 0.0384078 17.3195 0.0384078 16.9289 0.428932C16.5384 0.819457 16.5384 1.45262 16.9289 1.84315L22.5858 7.5L16.9289 13.1569C16.5384 13.5474 16.5384 14.1805 16.9289 14.5711C17.3195 14.9616 17.9526 14.9616 18.3431 14.5711L24.7071 8.20711ZM0 8.5H24V6.5H0V8.5Z"
                            fill="white" />
                    </svg></a>
                </a>
            <?php endif; ?>

        </div>
    </div>
</main>

<?php get_footer() ?>