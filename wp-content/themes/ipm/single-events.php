<?php
/*
Template Name: Single Events
*/
get_header() ?>

<main class="single-events-page">
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
    <section class="single-events">
        <div class="container">
            <a href="<?php echo get_post_type_archive_link('events'); ?>" class="button">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="23" viewBox="0 0 30 23" fill="none">
                    <path
                        d="M0.93934 10.4393C0.353554 11.0251 0.353554 11.9749 0.939341 12.5607L10.4853 22.1066C11.0711 22.6924 12.0208 22.6924 12.6066 22.1066C13.1924 21.5208 13.1924 20.5711 12.6066 19.9853L4.12132 11.5L12.6066 3.01472C13.1924 2.42893 13.1924 1.47919 12.6066 0.8934C12.0208 0.307613 11.0711 0.307613 10.4853 0.8934L0.93934 10.4393ZM30 10L2 10L2 13L30 13L30 10Z"
                        fill="#724491" />
                </svg>
                <span>Back to events</span>
            </a>


            <div class="single-events__item">
                <div class="single-events__item_top">
                    <div class="badge"><?php the_field('event_type'); ?></div>
                    <div class="title-sm"><?php the_title(); ?></div>
                </div>

                <div class="single-events__item_bottom">
                    <div class="single-events__item_left">
                        <div class="name">OVERVIEW</div>
                        <div class="text"><?php the_field('event_overview'); ?></div>
						<br>
                        <div class="name">CPD</div>
                        <div class="text"><?php the_field('event_cpd'); ?></div>
                    </div>

                    <div class="single-events__item_right">
                        <div class="name">DATE & TIME</div>
                        <div class="text time"><?php the_field('event_date'); ?> <br> <?php the_field('event_time'); ?>
                        </div>

                        <div class="speakers">
                            <div class="name">SPEAKERS</div>
                            <?php if (have_rows('event_speakers')): ?>
                                <?php while (have_rows('event_speakers')):
                                    the_row(); ?>
                                    <div class="speakers__item">
                                        <div class="speakers__image">
                                            <img src="<?php the_sub_field('speaker_image'); ?>"
                                                alt="<?php the_sub_field('speaker_name'); ?>">
                                        </div>
                                        <div class="speakers__text">
                                            <div class="text"><strong><?php the_sub_field('speaker_name'); ?></strong>
                                                <?php the_sub_field('speaker_title'); ?></div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="text">TBC</div>
                            <?php endif; ?>
                        </div>

                        <div class="name">REGISTRATION</div>

							<?php 
							$registration_link = get_field('event_registration_link'); 
							$event_date_raw = get_field('event_date'); 
							$today = date('Ymd');

							$event_date = DateTime::createFromFormat('F j, Y', $event_date_raw);
							$event_date_formatted = $event_date ? $event_date->format('Ymd') : null;

							
							if ($registration_link && $event_date_formatted && $event_date_formatted >= $today): ?>
								<a 
									<?php if(get_field('new_tab', get_the_ID())) { echo 'target="_blank"'; } ?> 
									href="<?php echo esc_url($registration_link); ?>?event_id=<?php echo get_the_ID(); ?>" 
									class="btn">REGISTER NOW</a>
							<?php else: ?>
								<?php if(get_field('event_presentation_link')):?>
								<a 
								<?php if(get_field('new_tab', get_the_ID())) { echo 'target="_blank"'; } ?> 
											href="<?php echo get_field('event_presentation_link') ?>" 
											class="btn"><?php echo get_field('event_presentation_text') ?></a>
								<?php endif;?>
								
							<?php endif; ?>


                </div>
            </div>
        </div>
    </section>

    <div class="more">
        <div class="container">
            <div class="text-40 text-purple">More events</div>
            <div class="more__items">
                <?php
$today = date('Y-m-d');

    $args = array(
        'post_type' => 'Events', 
        'posts_per_page' => 4,  
        'meta_key' => 'event_date',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'meta_query' => array(
            array(
                'key' => 'event_date',
                'value' => $today,
                'compare' => '>=',
                'type' => 'DATE'
            )
        )
    );

                $more_events_query = new WP_Query($args);

                if ($more_events_query->have_posts()):
                    while ($more_events_query->have_posts()):
                        $more_events_query->the_post(); ?>
                        <div class="more__item">
                            <div class="badge"><?php the_field('event_type'); ?></div>
                            <div class="title-sm"><?php the_title(); ?></div>
                            <div class="text"><?php the_field('event_date'); ?> <br> <?php the_field('event_time'); ?></div>
                            <a href="<?php the_permalink(); ?>" class="button">
                                <span>See details</span>
                                <svg width="30" height="23" viewBox="0 0 30 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M29.0607 12.5607C29.6464 11.9749 29.6464 11.0251 29.0607 10.4393L19.5147 0.893398C18.9289 0.307611 17.9792 0.307611 17.3934 0.893398C16.8076 1.47919 16.8076 2.42893 17.3934 3.01472L25.8787 11.5L17.3934 19.9853C16.8076 20.5711 16.8076 21.5208 17.3934 22.1066C17.9792 22.6924 18.9289 22.6924 19.5147 22.1066L29.0607 12.5607ZM0 13H28V10H0V13Z"
                                        fill="#724491" />
                                </svg>
                            </a>

                        </div>
                    <?php endwhile;
                else:
                    echo '<p>No upcoming events.</p>';
                endif;

                // Сбрасываем данные запроса
                wp_reset_postdata();
                ?>
            </div>
        </div>
    </div>

</main>


<?php get_footer() ?>