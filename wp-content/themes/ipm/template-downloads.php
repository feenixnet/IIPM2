<?php
/*
Template Name: Downloads Page
*/
get_header() ?>

<main class="downloads">
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
            <div class="text"><?php echo get_field('download_text'); ?></div>
        </div>
    </div>
    <div class="browse">
        <div class="container">
            <div class="text-40 "><?php echo get_field('browse_title'); ?></div>
            <div class="browse__items">
                <?php
                if (have_rows('browse_repeater')):
                    while (have_rows('browse_repeater')):
                        the_row();
                        ?>
                        <div class="browse__item">
                            <div class="browse__top">
                                <div class="text-35"><?php echo get_sub_field('title'); ?></div>
                                <?php
                                $image = get_sub_field('image');

                                if ($image) {
                                    echo '<img src="' . esc_url($image) . '" alt="">';
                                }
                                ?>
                            </div>
                            <?php
                            $link = get_sub_field('link');
                            if ($link):
                                $link_url = $link['url'];
                                $link_title = $link['title'];
                                $link_target = $link['target'] ? $link['target'] : '_self';
                                ?>
                                <a class="button" href="<?php echo esc_url($link_url); ?>"
                                    target="<?php echo esc_attr($link_target); ?>"><?php echo esc_html($link_title); ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="23" viewBox="0 0 30 23" fill="none">
                                        <path
                                            d="M29.0607 12.5607C29.6464 11.9749 29.6464 11.0251 29.0607 10.4393L19.5147 0.893398C18.9289 0.307611 17.9792 0.307611 17.3934 0.893398C16.8076 1.47919 16.8076 2.42893 17.3934 3.01472L25.8787 11.5L17.3934 19.9853C16.8076 20.5711 16.8076 21.5208 17.3934 22.1066C17.9792 22.6924 18.9289 22.6924 19.5147 22.1066L29.0607 12.5607ZM0 13H28V10H0V13Z"
                                            fill="#6B6B6B" />
                                    </svg>
                                </a>
                            <?php endif; ?>

                        </div>

                    <?php endwhile;
                else:
                endif;
                ?>

            </div>
        </div>
    </div>
    <?php
    $args = array(
        'post_type' => 'downloads',
        'posts_per_page' => 10,
        'orderby' => 'date',
        'order' => 'DESC',
    );

    $query = new WP_Query($args);
    if ($query->have_posts()):
        ?>
        <div class="files">
            <div class="container">
                <div class="text-40">Recently uploaded files</div>
                <div class="files__wrapper">
                    <div class="files__top">
                        <div class="name">FILE NAME</div>
                        <div class="name">DATE UPLOADED</div>
                        <div class="name">CATEGORY</div>
                        <div class="name">ACTION</div>
                    </div>
                    <?php while ($query->have_posts()):
                        $query->the_post(); ?>
                        <?php
                        $file = get_field('file');
                        $categories = get_the_terms(get_the_ID(), 'download_category');
                        $category_name = $categories ? esc_html($categories[0]->name) : 'Uncategorized';
                        ?>
                        <div class="files__item">
                            <div class="text"><?php the_title(); ?></div>
                            <div class="text"><?php echo get_the_date('Y-m-d'); ?></div>
                            <div class="text"><?php echo $category_name; ?></div>
                            <div class="block">
                                <?php if ($file): ?>
                                    <a href="<?php echo esc_url($file['url']); ?>" class="download-icon" title="Download File"
                                        download>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                            fill="none">
                                            <path
                                                d="M16.59 9H15V4C15 3.45 14.55 3 14 3H10C9.45 3 9 3.45 9 4V9H7.41C6.52 9 6.07 10.08 6.7 10.71L11.29 15.3C11.68 15.69 12.31 15.69 12.7 15.3L17.29 10.71C17.92 10.08 17.48 9 16.59 9ZM5 19C5 19.55 5.45 20 6 20H18C18.55 20 19 19.55 19 19C19 18.45 18.55 18 18 18H6C5.45 18 5 18.45 5 19Z"
                                                fill="black" />
                                        </svg>
                                    </a>
                                    <a href="<?php echo esc_url($file['url']); ?>" class="see-icon" title="View File"
                                        target="_blank">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="14" viewBox="0 0 22 14"
                                            fill="none">
                                            <mask id="mask0_2054_913" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="0"
                                                y="0" width="22" height="14">
                                                <path
                                                    d="M11 13C16.523 13 21 7 21 7C21 7 16.523 1 11 1C5.477 1 1 7 1 7C1 7 5.477 13 11 13Z"
                                                    fill="white" stroke="white" stroke-width="2" stroke-linejoin="round" />
                                                <path
                                                    d="M11 9.5C11.663 9.5 12.2989 9.23661 12.7678 8.76777C13.2366 8.29893 13.5 7.66304 13.5 7C13.5 6.33696 13.2366 5.70107 12.7678 5.23223C12.2989 4.76339 11.663 4.5 11 4.5C10.337 4.5 9.70107 4.76339 9.23223 5.23223C8.76339 5.70107 8.5 6.33696 8.5 7C8.5 7.66304 8.76339 8.29893 9.23223 8.76777C9.70107 9.23661 10.337 9.5 11 9.5Z"
                                                    fill="black" stroke="black" stroke-width="2" stroke-linejoin="round" />
                                            </mask>
                                            <g mask="url(#mask0_2054_913)">
                                                <path d="M-1 -5H23V19H-1V-5Z" fill="black" />
                                            </g>
                                        </svg>
                                    </a>
                                <?php else: ?>
                                    <span class="text">No file</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <a href="<?php echo get_post_type_archive_link('downloads'); ?>" class="see-all">
                    See all files
                    <svg xmlns="http://www.w3.org/2000/svg" width="25" height="15" viewBox="0 0 25 15" fill="none">
                        <path
                            d="M24.7071 8.20711C25.0976 7.81658 25.0976 7.18342 24.7071 6.79289L18.3431 0.428932C17.9526 0.0384078 17.3195 0.0384078 16.9289 0.428932C16.5384 0.819457 16.5384 1.45262 16.9289 1.84315L22.5858 7.5L16.9289 13.1569C16.5384 13.5474 16.5384 14.1805 16.9289 14.5711C17.3195 14.9616 17.9526 14.9616 18.3431 14.5711L24.7071 8.20711ZM0 8.5H24V6.5H0V8.5Z"
                            fill="white" />
                    </svg>
                </a>
            </div>
        </div>
        <?php
        wp_reset_postdata();
    endif;
    ?>
	
	<style>
		@media(min-width:768px){
			.files__item .text{
	min-width: 120px;
}	
		
		.files__top .name{
			min-width: 120px;
		}
		.files__top .name:nth-child(3), .files__top .name:nth-child(4){
text-align: right;}
		}

</style>

    <div class="connect">
        <div class="container">
            <div class="connect__wrapper">
                <div class="connect__left">
                    <div class="text-40"><?php echo get_field('connect_title'); ?></div>
                    <div class="text"><?php echo get_field('connect_text'); ?></div>
                </div>
                <div class="connect__right">
                    <?php
                    $link = get_field('connect_link');
                    if ($link):
                        $link_url = $link['url'];
                        $link_title = $link['title'];
                        $link_target = $link['target'] ? $link['target'] : '_self';
                        ?>
                        <a class="button" href="<?php echo esc_url($link_url); ?>"
                            target="<?php echo esc_attr($link_target); ?>"><?php echo esc_html($link_title); ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="23" viewBox="0 0 30 23" fill="none">
                                <path
                                    d="M29.0607 12.5607C29.6464 11.9749 29.6464 11.0251 29.0607 10.4393L19.5147 0.893398C18.9289 0.307611 17.9792 0.307611 17.3934 0.893398C16.8076 1.47919 16.8076 2.42893 17.3934 3.01472L25.8787 11.5L17.3934 19.9853C16.8076 20.5711 16.8076 21.5208 17.3934 22.1066C17.9792 22.6924 18.9289 22.6924 19.5147 22.1066L29.0607 12.5607ZM0 13H28V10H0V13Z"
                                    fill="#00605C" />
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php get_footer() ?>