<?php

get_header() ?>

<main class="events">
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
    <div class="block-text">
        <div class="container">

            <div class="text"><?php echo get_field('events_text', 'options'); ?></div>

        </div>
    </div>
    <div class="event">
        <div class="container">
            <div class="title-md text-purple">Upcoming Events</div>

            <?php
          $today = date('Ymd'); // текущая дата в формате Ymd

$args = array(
    'post_type' => 'events',
    'posts_per_page' => -1,
    'orderby' => 'meta_value',
    'meta_key' => 'event_date',
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
                    <div class="event__item">
                        <div class="event__item_top">
                            <div class="badge"><?php the_field('event_type'); ?></div>
                            <div class="title-sm"><?php the_title(); ?></div>
                        </div>
                        <div class="event__item_bottom">
                            <div class="event__item_left">
                                <div class="block">
                                    <div class="name">DATE & TIME</div>
                                    <div class="text"><?php the_field('event_date'); ?> <br> <?php the_field('event_time'); ?>
                                    </div>
                                </div>
                                <div class="block">
                                    <div class="name">SPEAKERS</div>
                                    <div class="text">
                                        <?php if (have_rows('event_speakers')): ?>
                                            <?php while (have_rows('event_speakers')):
                                                the_row(); ?>
                                                <div class="speaker">
                                                    <strong><?php the_sub_field('speaker_name'); ?></strong>
                                                </div>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <p>No speakers listed.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="block">
                                    <div class="name">CPD</div>
                                    <div class="text"><?php the_field('event_cpd'); ?></div>
                                </div>
                            </div>
                            <div class="event__item_right">
								<?php if(get_field('event_registration_link', get_the_ID())):?>
                                <a <?php if(get_field('new_tab', get_the_ID())){echo 'target="_blank"';} ?> href="<?php echo get_field('event_registration_link', get_the_ID()); ?>" class="btn">REGISTER NOW</a>
								<?php endif; ?>
                                <a href="<?php the_permalink(); ?>" class="button">
                                    <span>See details</span>
                                    <svg width="30" height="23" viewBox="0 0 30 23" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M29.0607 12.5607C29.6464 11.9749 29.6464 11.0251 29.0607 10.4393L19.5147 0.893398C18.9289 0.307611 17.9792 0.307611 17.3934 0.893398C16.8076 1.47919 16.8076 2.42893 17.3934 3.01472L25.8787 11.5L17.3934 19.9853C16.8076 20.5711 16.8076 21.5208 17.3934 22.1066C17.9792 22.6924 18.9289 22.6924 19.5147 22.1066L29.0607 12.5607ZM0 13H28V10H0V13Z"
                                            fill="#724491" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php
                endwhile;
            else:
                echo '<p>No upcoming events.</p>';
            endif;

            wp_reset_postdata();
            ?>

        </div>
    </div>

    <div class="help">
        <div class="container">
			
			 		<div class="links">
                        <div class="text-40">Past Events</div>
                        <div class="text">Download materials from past events</div>
                        <?php
								$today = date('Ymd'); // текущая дата в формате Ymd

								$args = array(
									'post_type' => 'events',
									'posts_per_page' => -1,
									'orderby' => 'meta_value',
									'meta_key' => 'event_date',
									'order' => 'DESC',
									'meta_query' => array(
										array(
											'key' => 'event_date',
											'compare' => '<',
											'value' => $today,
											'type' => 'NUMERIC'
										)
									)
								);

								$past_events = new WP_Query($args);

								if ($past_events->have_posts()): ?>
									<div class="past-events">
										<?php while ($past_events->have_posts()): $past_events->the_post(); ?>
											
												
													<a class="button" href="<?php echo esc_url(get_permalink()); ?>" >
														<span><?php the_title(); ?></span>
														<svg width="30" height="23" viewBox="0 0 30 23" fill="none" xmlns="http://www.w3.org/2000/svg">
															<path
																d="M29.0607 12.5607C29.6464 11.9749 29.6464 11.0251 29.0607 10.4393L19.5147 0.893398C18.9289 0.307611 17.9792 0.307611 17.3934 0.893398C16.8076 1.47919 16.8076 2.42893 17.3934 3.01472L25.8787 11.5L17.3934 19.9853C16.8076 20.5711 16.8076 21.5208 17.3934 22.1066C17.9792 22.6924 18.9289 22.6924 19.5147 22.1066L29.0607 12.5607ZM0 13H28V10H0V13Z"
																fill="#724491" />
														</svg>
													</a>

									
										<?php endwhile; ?>
									</div>
								<?php else: ?>
									<p>No past events found.</p>
								<?php endif;

								wp_reset_postdata();
								?>
                    </div>
			
			
            <?php
            if (have_rows('help_repeater', 'options')):
                while (have_rows('help_repeater', 'options')):
                    the_row();
                    ?>
                    <div class="links">
                        <div class="text-40"><?php echo get_sub_field('title'); ?></div>
                        <div class="text"><?php echo get_sub_field('text'); ?></div>
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