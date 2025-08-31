<?php
/*
Template Name: Courses Page
*/
get_header() ?>

<main class="courses">
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
    <div class="programm">
        <div class="container">
            <div class="programm__wrapper">
                <div class="programm__left">
                    <div class="title-md text-orange"><?php echo get_field('title'); ?></div>
                    <?php
                    if (have_rows('programm_text')):
                        while (have_rows('programm_text')):
                            the_row();
                            ?>
                            <div class="text"><?php echo get_sub_field('text'); ?></div>
                        <?php endwhile;
                    else:
                    endif;
                    ?>
                    <div class="programm__right">
                        <?php
                        $image = get_field('programm_logo');
                        if ($image): ?>
                            <img src="<?php echo esc_url($image); ?>" alt="">
                        <?php endif; ?>
                    </div>
                </div>
                <div class="programm__right">
                    <?php
                    $image = get_field('programm_image');
                    if ($image): ?>
                        <img src="<?php echo esc_url($image); ?>" alt="">
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="services">
        <div class="container">
            <div class="services__items">
                <?php
                if (have_rows('services')):
                    $count = 1; 
                    while (have_rows('services')):
                        the_row();
                        $block_id = 'course-' . $count;
                        ?>
                        <div class="services__item" id="<?php echo esc_attr($block_id); ?>">
                            <div class="services__left">
                                <?php
                                $image = get_sub_field('image');
                                if ($image) {
                                    echo '<img src="' . esc_url($image) . '" alt="">';
                                }
                                ?>
                                <div class="block">
                                    <a href="<?php echo get_sub_field('links')[0]['link']['url']?>" class="text-40" style="display: block; text-decoration: none;"><?php echo esc_html(get_sub_field('title')); ?></a>
                                    <?php
                                    if (have_rows('texts')):
                                        while (have_rows('texts')):
                                            the_row();
                                            ?>
                                            <div class="text"><?php echo get_sub_field('text'); ?></div>
                                        <?php
                                        endwhile;
                                    endif;
                                    ?>
                                </div>
                            </div>
                            <div class="services__right">
                                <div class="text-30"><?php echo get_sub_field('link_title'); ?></div>
                                <?php
                                if (have_rows('links')):
                                    while (have_rows('links')):
                                        the_row();
                                        $link = get_sub_field('link');
                                        if ($link):
                                            $link_url = $link['url'];
                                            $link_title = $link['title'];
                                            $link_target = $link['target'] ? $link['target'] : '_self';
                                            ?>
                                            <a target="_blank" class="button" href="<?php echo esc_url($link_url); ?>"
                                                target="<?php echo esc_attr($link_target); ?>">
                                                <span><?php echo esc_html($link_title); ?></span>
                                                <svg width="30" height="23" viewBox="0 0 30 23" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M29.0607 12.5607C29.6464 11.9749 29.6464 11.0251 29.0607 10.4393L19.5147 0.893398C18.9289 0.307611 17.9792 0.307611 17.3934 0.893398C16.8076 1.47919 16.8076 2.42893 17.3934 3.01472L25.8787 11.5L17.3934 19.9853C16.8076 20.5711 16.8076 21.5208 17.3934 22.1066C17.9792 22.6924 18.9289 22.6924 19.5147 22.1066L29.0607 12.5607ZM0 13H28V10H0V13Z"
                                                        fill="#724491" />
                                                </svg>
                                            </a>
                                        <?php
                                        endif;
                                    endwhile;
                                endif;
                                ?>
								
								
								<?php if(get_sub_field("button_text")):?>
								
									<a class="btn" <?php if(get_sub_field('new_tab')){echo 'target="_blank"'; }?>href="<?php echo get_sub_field('button_link');?>">
										<?php echo get_sub_field('button_text');?>
									</a>
								
								<?php endif; ?>
                            </div>
                        </div>
                        <?php
                        $count++; // Увеличиваем счетчик
                    endwhile;
                endif;
                ?>
            </div>
        </div>
    </div>
	
	<style>
		@media(min-width: 991px){
			.services__right .btn{
margin-top: auto; 
			}
		}
	</style>

    <div class="start">
        <div class="container">
            <div class="start__wrapper">
                <div class="start__left">
                    <div class="title-md"><?php echo get_field('start_title'); ?></div>
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
.services__left img{
	max-width: 80px;
}
	@media(min-width: 767px){
		.services__right{
min-width: 380px;}
	}
	
</style>

<?php get_footer() ?>