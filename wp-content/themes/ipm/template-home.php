<?php
/*
Template Name: Home Page
*/
get_header() ?>

<main>



    <div class="main-banner">
        <?php
        $image = get_field('banner');
        if ($image): ?>
            <img src="<?php echo esc_url($image); ?>" alt="" class="w-full">
		
		<?php elseif (get_field('video')):?>
			<video autoplay loop muted playinline>
			<source src="<?php echo get_field('video');?>"  type="video/mp4">
		</video>
        <?php endif; ?>
		
		
    </div>
	
	<style>
		.main-banner video{
		
				max-height: 50vh;
				object-fit: cover;
				width: 100%;
		
		}
	</style>

    <div class="provider">
        <div class="container">
            <div class="provider__inner">
                <div class="provider__content">
                    <h1 class="title home-title-lg">
                        <?php echo get_field('provider_title'); ?>
                    </h1>

                    <div class="text">
                        <?php echo get_field('provider_text'); ?>
                    </div>
                    <?php
                    $link = get_field('button', 'option');
                    if ($link):
                        $link_url = $link['url'];
                        $link_title = $link['title'];
                        $link_target = $link['target'] ? $link['target'] : '_self';
                        ?>
                        <a class="button" href="<?php echo esc_url($link_url); ?>"
                            target="<?php echo esc_attr($link_target); ?>"><?php echo esc_html($link_title); ?><svg
                                width="30" height="23" viewBox="0 0 30 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M29.0607 12.5607C29.6464 11.9749 29.6464 11.0251 29.0607 10.4393L19.5147 0.893398C18.9289 0.307611 17.9792 0.307611 17.3934 0.893398C16.8076 1.47919 16.8076 2.42893 17.3934 3.01472L25.8787 11.5L17.3934 19.9853C16.8076 20.5711 16.8076 21.5208 17.3934 22.1066C17.9792 22.6924 18.9289 22.6924 19.5147 22.1066L29.0607 12.5607ZM0 13H28V10H0V13Z"
                                    fill="#724491" />
                            </svg></a>
                    <?php endif; ?>

                </div>

                <div class="provider__events box-shadow">
                    <div class="title-sm provider__events-title"><?php echo get_field('provider_block_title'); ?></div>

                <div class="events__list">
					<?php
					$today = date('Ymd'); // Текущая дата в формате Ymd

					$args = array(
						'post_type' => 'events',
						'posts_per_page' => 4,
						'meta_key' => 'event_date',
						'orderby' => 'meta_value',
						'order' => 'ASC',
						'meta_query' => array(
							array(
								'key' => 'event_date',
								'compare' => '>=',
								'value' => $today,
								'type' => 'NUMERIC'
							)
						)
					);

					$events_query = new WP_Query($args);

					if ($events_query->have_posts()):
						while ($events_query->have_posts()):
							$events_query->the_post();
							?>
							<a href="<?php the_permalink(); ?>" class="events__list-item">
								<div class="date"><?php the_field('event_date'); ?></div>
								<div class="descr"><?php the_title(); ?></div>
							</a>
							<?php
						endwhile;
						wp_reset_postdata();
					else:
						echo '<p>No upcoming events.</p>';
					endif;
					?>
				</div>


                    <a href="<?php echo get_post_type_archive_link('events'); ?>" class="button uppercase">
                        <span class="text-white">SEE MORE</span>
                        <svg width="20" height="16" viewBox="0 0 20 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M19.7071 8.70711C20.0976 8.31658 20.0976 7.68342 19.7071 7.29289L13.3431 0.928932C12.9526 0.538408 12.3195 0.538408 11.9289 0.928932C11.5384 1.31946 11.5384 1.95262 11.9289 2.34315L17.5858 8L11.9289 13.6569C11.5384 14.0474 11.5384 14.6805 11.9289 15.0711C12.3195 15.4616 12.9526 15.4616 13.3431 15.0711L19.7071 8.70711ZM0 9H19V7H0V9Z"
                                fill="white" />
                        </svg>
                    </a>


                </div>
            </div>
        </div>
    </div>



    <div class="text-image">
        <div class="container">
            <div class="text-image__inner">
                <div class="text-image__content">
                    <div class="home-title-md text-purple"><?php echo get_field('block_title'); ?></div>
                    <div class="text"><?php echo get_field('block_text'); ?></div>
                </div>
                <div class="text-image__img">
                    <?php
                    $image = get_field('block_image');
                    if ($image): ?>
                        <img src="<?php echo esc_url($image); ?>" alt="" class="w-full">
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
    <div class="industry">
        <div class="container">
            <div class="industry__inner">
                <div class="home-title-md"><?php echo get_field('industry_title'); ?></div>
                <div class="text"><?php echo get_field('industry_text'); ?></div>

                <div class="industry__cards">
                    <?php
                    // Manual approach due to ACF migration issue - data exists but get_field() doesn't assemble it
                    $count = get_post_meta(get_the_ID(), 'industry_repeater', true);
                    // Get all meta once to avoid individual get_post_meta issues
                    $all_meta = get_post_meta(get_the_ID());
                    
                    if ($count > 0):
                        for ($i = 0; $i < $count; $i++):
                            // Use array access instead of get_post_meta for problematic fields
                            $title = isset($all_meta["industry_repeater_{$i}_title"]) ? $all_meta["industry_repeater_{$i}_title"][0] : '';
                            $image_id = isset($all_meta["industry_repeater_{$i}_image"]) ? $all_meta["industry_repeater_{$i}_image"][0] : '';
                            $link_data = isset($all_meta["industry_repeater_{$i}_link"]) ? $all_meta["industry_repeater_{$i}_link"][0] : '';
                            // Try multiple unserialization methods due to encoding issues
                            $link = maybe_unserialize($link_data);
                            if (!$link) {
                                $link = @unserialize($link_data);
                            }
                            if (!$link) {
                                $link = @unserialize(stripslashes($link_data));
                            }
                            // Manual parsing fallback
                            if (!$link && strpos($link_data, 'a:3:') === 0) {
                                $link = array();
                                if (preg_match('/s:3:"url";s:\d+:"([^"]+)"/', $link_data, $matches)) {
                                    $link['url'] = $matches[1];
                                }
                                if (preg_match('/s:5:"title";s:\d+:"([^"]+)"/', $link_data, $matches)) {
                                    $link['title'] = $matches[1];
                                }
                                if (preg_match('/s:6:"target";s:\d+:"([^"]*)"/', $link_data, $matches)) {
                                    $link['target'] = $matches[1];
                                }
                            }
                            $image_url = wp_get_attachment_url($image_id);
                            ?>
                            <div class="industry__cards-item">
                                <?php 
                                // Show image (with or without link)
                                if ($image_url): 
                                    if ($link && isset($link['url'])): ?>
                                        <a href="<?php echo esc_url($link['url']); ?>">
                                            <img src="<?php echo esc_url($image_url); ?>" alt="" class="box-shadow">
                                        </a>
                                    <?php else: ?>
                                        <img src="<?php echo esc_url($image_url); ?>" alt="" class="box-shadow">
                                    <?php endif; 
                                endif; ?>

                                <div class="title-xs"><?php echo esc_html($title); ?></div>

                                <?php 
                                if ($link && is_array($link) && isset($link['url']) && $link['url']): 
                                    $link_url = $link['url'];
                                    $link_title = isset($link['title']) && $link['title'] ? $link['title'] : 'Learn more';
                                    $link_target = isset($link['target']) && $link['target'] ? $link['target'] : '_self';
                                    ?>
                                    <a class="button " href="<?php echo esc_url($link_url); ?>"
                                        target="<?php echo esc_attr($link_target); ?>">

                                        <span class="text-orange"><?php echo esc_html($link_title); ?></span>

                                        <svg width="30" height="23" viewBox="0 0 30 23" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M29.0607 12.5607C29.6464 11.9749 29.6464 11.0251 29.0607 10.4393L19.5147 0.893398C18.9289 0.307611 17.9792 0.307611 17.3934 0.893398C16.8076 1.47919 16.8076 2.42893 17.3934 3.01472L25.8787 11.5L17.3934 19.9853C16.8076 20.5711 16.8076 21.5208 17.3934 22.1066C17.9792 22.6924 18.9289 22.6924 19.5147 22.1066L29.0607 12.5607ZM0 13H28V10H0V13Z"
                                                fill="#F18700" />
                                        </svg>
                                    </a>
                                <?php endif; ?>
                            </div>

                        <?php endfor;
                    else:
                        echo '<p>No industry items found.</p>';
                    endif;
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="awards">
        <div class="container">
            <div class="awards__inner">
                <div class="awards__items">
                    <?php
                    // Manual approach for awards_repeater - same fix as industry section
                    $all_meta = get_post_meta(get_the_ID());
                    $awards_count = isset($all_meta['awards_repeater']) ? $all_meta['awards_repeater'][0] : 0;
                    
                    if ($awards_count > 0):
                        for ($i = 0; $i < $awards_count; $i++):
                            $title = isset($all_meta["awards_repeater_{$i}_title"]) ? $all_meta["awards_repeater_{$i}_title"][0] : '';
                            $text = isset($all_meta["awards_repeater_{$i}_text"]) ? $all_meta["awards_repeater_{$i}_text"][0] : '';
                            $image_id = isset($all_meta["awards_repeater_{$i}_image"]) ? $all_meta["awards_repeater_{$i}_image"][0] : '';
                            $image_url = $image_id ? wp_get_attachment_url($image_id) : '';
                            ?>
                            <div class="awards__item">
                                <div class="home-title-md title-purple"><?php echo esc_html($title); ?></div>
                                <div class="text"><?php echo esc_html($text); ?></div>
                                <?php if ($image_url): ?>
                                    <img src="<?php echo esc_url($image_url); ?>" alt="">
                                <?php endif; ?>
                            </div>

                        <?php endfor;
                    endif;
                    ?>

                </div>
                <div class="awards__block">
                    <div class="home-title-md title-purple"><?php 
                        $awards_title = isset($all_meta['awards_title']) ? $all_meta['awards_title'][0] : '';
                        echo esc_html($awards_title); 
                    ?></div>
<!--                     <div class="awards__block_images">
                        <?php
                        if (have_rows('awards_images')):
                            while (have_rows('awards_images')):
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
                    </div> -->
					
					
					<div class="swiper awards-slider">
						<div class="swiper-wrapper">
							<?php 
							// Manual approach for awards_images slider
							$awards_images_count = isset($all_meta['awards_images']) ? $all_meta['awards_images'][0] : 0;
							if ($awards_images_count > 0):
								for ($i = 0; $i < $awards_images_count; $i++):
									$image_id = isset($all_meta["awards_images_{$i}_image"]) ? $all_meta["awards_images_{$i}_image"][0] : '';
									$image_url = $image_id ? wp_get_attachment_url($image_id) : '';
									if ($image_url): ?>
										<div class="swiper-slide">
											<img src="<?php echo esc_url($image_url); ?>" alt="">
										</div>
									<?php endif;
								endfor;
							endif; ?>
						</div>
				
						<div class="swiper-pagination"></div>
					</div>
					
					
					<script>
document.addEventListener("DOMContentLoaded", function() {
    new Swiper('.awards-slider', {
        slidesPerView: 2,
        spaceBetween: 32,
        loop: true,
        autoplay: {
            delay: 2000,
            disableOnInteraction: true
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true
        },
    
        breakpoints: {
            640: { slidesPerView: 2 },
            768: { slidesPerView: 3 },
            1024: { slidesPerView: 4 },
            1200: { slidesPerView: 5 }
        }
    });
});
</script>

					

					
                </div>
            </div>
        </div>
    </div>

    <div class="join">
        <div class="container">
            <div class="join__wrapper">
                <div class="join__left">
                    <div class="home-title-md"><?php echo get_field('join_title'); ?></div>
                    <div class="text"><?php echo get_field('join_text'); ?></div>
                </div>
                <div class="join__right form-container">
                    <?php echo do_shortcode('[contact-form-7 id="1292e83" title="Form join"]'); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="start">
        <div class="container">
            <div class="start__wrapper">
                <div class="start__left">
                    <div class="home-title-md title-purple"><?php echo get_field('start_title'); ?></div>
                    <div class="text"><?php echo get_field('start_text'); ?></div>
                </div>
                <?php
                $link = get_field('start_link');
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

            </div>
        </div>
    </div>

</main>

<style>
    .home-title-lg {
        font-size: 68px;
        color: #000;
        font-style: normal;
        font-weight: 600;
        line-height: 70px;
        letter-spacing: -1px;
    }

    .home-title-md {
        font-size: 45px;
        font-style: normal;
        font-weight: 600;
        line-height: 49px;
        letter-spacing: -1px;
    }

    .title-purple {
        color: #724491;
    }
</style>


<?php get_footer() ?>