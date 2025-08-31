<?php
/*
Template Name: Event Registration
*/
get_header(); ?>

<main class="event-registration-page">
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
    <div class="event-form">
        <div class="container">
            <a href="<?php echo get_permalink(get_query_var('event_id')); ?>" class="button">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="23" viewBox="0 0 30 23" fill="none">
                    <path
                        d="M0.93934 10.4393C0.353554 11.0251 0.353554 11.9749 0.939341 12.5607L10.4853 22.1066C11.0711 22.6924 12.0208 22.6924 12.6066 22.1066C13.1924 21.5208 13.1924 20.5711 12.6066 19.9853L4.12132 11.5L12.6066 3.01472C13.1924 2.42893 13.1924 1.47919 12.6066 0.8934C12.0208 0.307613 11.0711 0.307613 10.4853 0.8934L0.93934 10.4393ZM30 10L2 10L2 13L30 13L30 10Z"
                        fill="#724491" />
                </svg>
                <span>Back to event details</span>
            </a>
        
            <?php
            $event_id = get_query_var('event_id');
            if ($event_id):
                $event = get_post($event_id);
            ?>
                <div class="event-form__wrapper">
                    <div class="event-form__left">
                        <div class="name">EVENT NAME</div>
                        <div class="title-sm"><?php echo get_the_title($event_id); ?></div>
        
                        <div class="name">DATE & TIME</div>
                        <div class="text time"><?php the_field('event_date', $event_id); ?> <br> <?php the_field('event_time', $event_id); ?></div>
        
                        <div class="speakers">
                            <div class="name">SPEAKERS</div>
                            <?php if (have_rows('event_speakers', $event_id)): ?>
                                <?php while (have_rows('event_speakers', $event_id)): the_row(); ?>
                                    <div class="speakers__item">
                                        <div class="speakers__image">
                                            <img src="<?php the_sub_field('speaker_image'); ?>" alt="<?php the_sub_field('speaker_name'); ?>">
                                        </div>
                                        <div class="speakers__text">
                                            <div class="text"><strong><?php the_sub_field('speaker_name'); ?></strong> <?php the_sub_field('speaker_title'); ?></div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </div>
        
                        <div class="name">CPD</div>
                        <div class="text"><?php the_field('event_cpd', $event_id); ?></div>
                    </div>
        
                    <div class="event-form__right">
                        <div class="text-40 text-purple">Event Registration</div>
                        <?php echo do_shortcode('[contact-form-7 id="13abdfc" title="Registration"]'); ?>
                    </div>
                </div>
            <?php else: ?>
                <p>No event data available.</p>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php get_footer(); ?>
