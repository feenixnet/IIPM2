<?php

get_header() ?>

<main class="downloads-all">
    <div class="banner">
        <?php
        $image = get_field('downloads_image', 'options');
        if ($image): ?>
            <img src="<?php echo esc_url($image); ?>" alt="" class="w-full">
        <?php endif; ?>
        <div class="container">
            <h1 class="title-lg"><?php echo get_field('downloads_title', 'options') ?></h1>
        </div>
    </div>
    <div class="files">
        <div class="container">
            <?php
            $link = get_field('downloads_link', 'options');
            if ($link):
                $link_url = $link['url'];
                $link_title = $link['title'];
                $link_target = $link['target'] ? $link['target'] : '_self';
                ?>
                <a class="button" href="<?php echo esc_url($link_url); ?>" target="<?php echo esc_attr($link_target); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="23" viewBox="0 0 30 23" fill="none">
                        <path
                            d="M0.93934 10.4393C0.353554 11.0251 0.353554 11.9749 0.939341 12.5607L10.4853 22.1066C11.0711 22.6924 12.0208 22.6924 12.6066 22.1066C13.1924 21.5208 13.1924 20.5711 12.6066 19.9853L4.12132 11.5L12.6066 3.01472C13.1924 2.42893 13.1924 1.47919 12.6066 0.8934C12.0208 0.307613 11.0711 0.307613 10.4853 0.8934L0.93934 10.4393ZM30 10L2 10L2 13L30 13L30 10Z"
                            fill="#724491" />
                    </svg>
                    <?php echo esc_html($link_title); ?></a>
            <?php endif; ?>

            <div class="text-40">All files</div>
			
			<style>
			
				<?php
					$categories = get_terms([
						'taxonomy' => 'download_category', 
						'hide_empty' => true,
					]);

					$current_filters = isset($_GET['filter']) ? explode(',', $_GET['filter']) : [];
					$current_sort = isset($_GET['sort']) ? $_GET['sort'] : '';
?>
				
				
			</style>
            <div class="filters">
				<div>
					
		
                <div class="filter text-purple">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="19" viewBox="0 0 18 19" fill="none">
                        <path
                            d="M17 0H1C0.734784 0 0.48043 0.105357 0.292893 0.292893C0.105357 0.48043 0 0.734784 0 1V3.227L0.0079999 3.45C0.0578081 4.11772 0.329563 4.74958 0.78 5.245L5 9.886V18C4.99996 18.1585 5.03758 18.3146 5.10975 18.4557C5.18192 18.5968 5.28657 18.7187 5.41509 18.8113C5.54361 18.904 5.69232 18.9648 5.84895 18.9888C6.00559 19.0127 6.16567 18.9991 6.316 18.949L12.316 16.949L12.424 16.906C12.5962 16.8254 12.7419 16.6973 12.8439 16.5369C12.9459 16.3764 13.0001 16.1902 13 16V9.414L17.121 5.294C17.3998 5.01525 17.621 4.68427 17.7718 4.31999C17.9226 3.95571 18.0002 3.56527 18 3.171V1C18 0.734784 17.8946 0.48043 17.7071 0.292893C17.5196 0.105357 17.2652 0 17 0Z"
                            fill="#724491" />
                    </svg>Filter
					
					
					
					
                </div>
				
				<div class="filter-options" style="display: none">
					<?php foreach ($categories as $cat): ?>
						<label>
							<input type="checkbox"
								   class="filter-checkbox"
								   value="<?php echo $cat->slug; ?>"
								   <?php echo in_array($cat->slug, $current_filters) ? 'checked' : ''; ?>>
							<?php echo $cat->name; ?>
						</label>
					<?php endforeach; ?>
				</div>
						</div>
				
				<div>
					
			
				
                <div class="sort text-purple" >
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="23" viewBox="0 0 14 23" fill="none">
                        <path
                            d="M6.01173 1.85962C6.55861 1.2981 7.44673 1.2981 7.99361 1.85962L13.5936 7.60962C13.9961 8.0229 14.1142 8.63833 13.8955 9.17739C13.6767 9.71645 13.1692 10.0668 12.6005 10.0668L1.40048 10.0624C0.836107 10.0624 0.324232 9.71196 0.105482 9.1729C-0.113268 8.63384 0.00923242 8.01841 0.407357 7.60513L6.00736 1.85513L6.01173 1.85962ZM6.01173 21.1446L0.411732 15.3946C0.00923242 14.9813 -0.108893 14.3659 0.109857 13.8268C0.328607 13.2877 0.836107 12.9374 1.40486 12.9374H12.6049C13.1692 12.9374 13.6811 13.2877 13.8999 13.8268C14.1186 14.3659 13.9961 14.9813 13.598 15.3946L7.99798 21.1446C7.45111 21.7061 6.56298 21.7061 6.01611 21.1446H6.01173Z"
                            fill="#724491" />
                    </svg>Sort
					
					
                </div>
				
				
					<div class="sort-options" style="display: none">
         <label>
    <input type="radio" name="sort" value="newest"
           class="sort-radio"
           <?php echo ($current_sort === 'newest') ? 'checked' : ''; ?>>
    Newest First
</label>
<label>
    <input type="radio" name="sort" value="oldest"
           class="sort-radio"
           <?php echo ($current_sort === 'oldest') ? 'checked' : ''; ?>>
    Oldest First
</label>
<label>
    <input type="radio" name="sort" value=""
           class="sort-radio"
           <?php echo ($current_sort === '') ? 'checked' : ''; ?>>
    Default
</label>
        </div>
					</div>
				
            </div>
			
			<script>
			
			
					function updateURLParam(param, values) {
						const url = new URL(window.location.href);
						if (values.length > 0) {
							url.searchParams.set(param, values.join(','));
						} else {
							url.searchParams.delete(param);
						}
						url.searchParams.delete('paged'); // сброс страницы
						window.location.href = url.toString();
					}

					document.querySelectorAll('.filter-checkbox').forEach(checkbox => {
						checkbox.addEventListener('change', () => {
							const selected = Array.from(document.querySelectorAll('.filter-checkbox:checked')).map(el => el.value);
							updateURLParam('filter', selected);
						});
					});

					document.querySelectorAll('.sort-radio').forEach(radio => {
						radio.addEventListener('change', () => {
							const selected = document.querySelector('.sort-radio:checked')?.value || '';
							updateURLParam('sort', [selected]);
						});
					});
			
			
			</script>
            <?php
           $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

$tax_query = [];
if (!empty($_GET['filter'])) {
    $filter_slugs = explode(',', sanitize_text_field($_GET['filter']));
    $tax_query[] = [
        'taxonomy' => 'download_category', // замените на вашу таксономию
        'field'    => 'slug',
        'terms'    => $filter_slugs,
        'operator' => 'IN',
    ];
}

$orderby = 'date';
$order = 'DESC';

if (!empty($_GET['sort'])) {
    $sort = sanitize_text_field($_GET['sort']);
   if ($sort === 'oldest') {
        $orderby = 'date';
        $order = 'ASC';
    } elseif ($sort === 'newest') {
        $orderby = 'date';
        $order = 'DESC';
    }
}

$args = array(
    'post_type' => 'downloads',
    'posts_per_page' => 15,
    'orderby' => $orderby,
    'order' => $order,
    'paged' => $paged,
);

if (!empty($tax_query)) {
    $args['tax_query'] = $tax_query;
}

$query = new WP_Query($args);

            $query = new WP_Query($args);
            if ($query->have_posts()): ?>
                <div class="files__wrapper">
                    <div class="files__top">
                        <div class="name">FILE NAME</div>
                        <div class="name">DATE UPLOADED</div>
                        <div class="name">CATEGORY</div>
                        <div class="name">ACTION</div>
                    </div>

                    <?php while ($query->have_posts()):
                        $query->the_post();
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
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

              <?php
			
			if ($query->max_num_pages > 1):
				?>
				<nav class="pagination">
					<?php if($paged > 1):?>
					<button class="pagination__prev" aria-label="Previous Page" onclick="navigatePage('prev')">
						<svg xmlns="http://www.w3.org/2000/svg" width="15" height="30" viewBox="0 0 15 30" fill="none">
							<path fill-rule="evenodd" clip-rule="evenodd"
								d="M2.30383 14.1114L9.37508 7.0402L11.1426 8.8077L4.95508 14.9952L11.1426 21.1827L9.37508 22.9502L2.30383 15.8789C2.06949 15.6445 1.93785 15.3267 1.93785 14.9952C1.93785 14.6637 2.06949 14.3459 2.30383 14.1114Z"
								fill="#6B6B6B" />
						</svg>
					</button>
					<?php endif;?>
					<span class="pagination__current" id="currentPage"><?php echo $paged; ?></span>
					<span class="pagination__of">of </span>
					<span class="pagination__total" id="totalPages"><?php echo $query->max_num_pages; ?></span>
					<?php if($paged < $query->max_num_pages):?>
					<button class="pagination__next" aria-label="Next Page" onclick="navigatePage('next')">
						<svg xmlns="http://www.w3.org/2000/svg" width="15" height="30" viewBox="0 0 15 30" fill="none">
							<path fill-rule="evenodd" clip-rule="evenodd"
								d="M12.6962 15.8886L5.62492 22.9598L3.85742 21.1923L10.0449 15.0048L3.85742 8.8173L5.62492 7.0498L12.6962 14.1211C12.9305 14.3555 13.0622 14.6733 13.0622 15.0048C13.0622 15.3363 12.9305 15.6541 12.6962 15.8886Z"
								fill="#6B6B6B" />
						</svg>
					</button>
					<?php endif;?>
				</nav>
				<?php
			endif;
			?>
            <?php endif;
            wp_reset_postdata(); ?>

        </div>
    </div>
</main>

<script>
	function navigatePage(direction) {
		const currentPageElement = document.getElementById('currentPage');
		const totalPagesElement = document.getElementById('totalPages');

		let currentPage = parseInt(currentPageElement.textContent);
		const totalPages = parseInt(totalPagesElement.textContent);

		if (direction === 'prev' && currentPage > 1) {
			currentPage--;
		} else if (direction === 'next' && currentPage < totalPages) {
			currentPage++;
		} else {
			return; 
		}

		
		const url = new URL(window.location.href);
		url.searchParams.set('paged', currentPage);

		
		window.location.href = url.toString();
	}
</script>


<style>
	.filter-options, .sort-options{
		display: flex;
		align-items: center;
		gap: 16px;
		
		flex-wrap: nowrap;
	}
	
	.filter-options label, .sort-options label{
		display: flex;
		align-items: center;
		gap: 8px;
		color: black;
		white-space: nowrap;
	}
	.filter-options input, .sort-options input{
		accent-color: #724491;
	}
	
	.filter.opened .filter-options{
		max-width: 100%;
	}
	
	.sort.opened .sort-options{
		max-width: 100%;
	}
	
	.filters > div{
		display: flex;
align-items: center;
	}
	.filters{
		flex-wrap: wrap;
	}
	
	@media(max-width: 767px){
		.filters > div{
			flex-direction: column;
			align-items: flex-start
		}
		
		.filter-options, .sort-options{
			padding-left: 16px;
			flex-wrap: wrap;
		}
		
	}
</style>

<script>
document.querySelector(".filter").addEventListener("click", function (e) {
    e.preventDefault();
    const options = document.querySelector(".filter-options");
    
    if (options.style.display === "none" || !options.style.display) {
        options.style.display = "flex";  // Показываем блок с фильтрами
    } else {
        options.style.display = "none";  // Скрываем блок с фильтрами
    }
});

document.querySelector(".sort").addEventListener("click", function (e) {
    e.preventDefault();
    const options = document.querySelector(".sort-options");

    if (options.style.display === "none" || !options.style.display) {
        options.style.display = "flex";  // Показываем блок с сортировкой
    } else {
        options.style.display = "none";  // Скрываем блок с сортировкой
    }
});

window.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);

    // Проверка для filter
    if (urlParams.has('filter')) {
        const filterOptions = document.querySelector(".filter-options");
        filterOptions.style.display = "flex";  // Показываем блок с фильтрами
    }

    // Проверка для sort
    if (urlParams.has('sort')) {
        const sortOptions = document.querySelector(".sort-options");
        sortOptions.style.display = "flex";  // Показываем блок с сортировкой
    }
});


</script>


<?php get_footer() ?>