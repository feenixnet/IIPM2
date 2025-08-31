<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package IPM
 */

get_header();
?>
<main class="newsroom-all-page">
	<div class="banner">
		<?php
		$image = get_field('blog_image', 'options');
		if ($image): ?>
			<img src="<?php echo esc_url($image); ?>" alt="" class="w-full">
		<?php endif; ?>

		<div class="container">
			<h1 class="title-lg"><?php echo get_field('blog_title', 'options'); ?></h1>
		</div>
	</div>
	<div class="newsroom-all">
		<div class="container">
			<div class="text-40">All articles</div>
			<div class="filters">
				<div class="filter text-purple">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="19" viewBox="0 0 18 19" fill="none">
						<path
							d="M17 0H1C0.734784 0 0.48043 0.105357 0.292893 0.292893C0.105357 0.48043 0 0.734784 0 1V3.227L0.0079999 3.45C0.0578081 4.11772 0.329563 4.74958 0.78 5.245L5 9.886V18C4.99996 18.1585 5.03758 18.3146 5.10975 18.4557C5.18192 18.5968 5.28657 18.7187 5.41509 18.8113C5.54361 18.904 5.69232 18.9648 5.84895 18.9888C6.00559 19.0127 6.16567 18.9991 6.316 18.949L12.316 16.949L12.424 16.906C12.5962 16.8254 12.7419 16.6973 12.8439 16.5369C12.9459 16.3764 13.0001 16.1902 13 16V9.414L17.121 5.294C17.3998 5.01525 17.621 4.68427 17.7718 4.31999C17.9226 3.95571 18.0002 3.56527 18 3.171V1C18 0.734784 17.8946 0.48043 17.7071 0.292893C17.5196 0.105357 17.2652 0 17 0Z"
							fill="#724491" />
					</svg>Filter
				</div>
				<div class="sort text-purple">
					<svg xmlns="http://www.w3.org/2000/svg" width="14" height="23" viewBox="0 0 14 23" fill="none">
						<path
							d="M6.01173 1.85962C6.55861 1.2981 7.44673 1.2981 7.99361 1.85962L13.5936 7.60962C13.9961 8.0229 14.1142 8.63833 13.8955 9.17739C13.6767 9.71645 13.1692 10.0668 12.6005 10.0668L1.40048 10.0624C0.836107 10.0624 0.324232 9.71196 0.105482 9.1729C-0.113268 8.63384 0.00923242 8.01841 0.407357 7.60513L6.00736 1.85513L6.01173 1.85962ZM6.01173 21.1446L0.411732 15.3946C0.00923242 14.9813 -0.108893 14.3659 0.109857 13.8268C0.328607 13.2877 0.836107 12.9374 1.40486 12.9374H12.6049C13.1692 12.9374 13.6811 13.2877 13.8999 13.8268C14.1186 14.3659 13.9961 14.9813 13.598 15.3946L7.99798 21.1446C7.45111 21.7061 6.56298 21.7061 6.01611 21.1446H6.01173Z"
							fill="#724491" />
					</svg>Sort
				</div>
			</div>

			<div class="newsroom-all__items">
				<?php
				$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

				$args = array(
					'post_type' => 'post',
					'posts_per_page' => 10, 
					'paged' => $paged, 
				);

				$query = new WP_Query($args);

				if ($query->have_posts()):
					while ($query->have_posts()):
						$query->the_post();
						?>
						<a class="newsroom-all__item" href="<?php the_permalink(); ?>">
							<?php
							if (has_post_thumbnail()):
								the_post_thumbnail('full', array('class' => 'newsroom-all__item_img'));
							endif;
							?>
							<div class="newsroom-all__item_content">
								<div class="newsroom-all__top">
									<div class="text-18"><?php echo get_the_category()[0]->name; ?></div>
									<div class="text-18"><?php echo get_the_date(); ?></div>
								</div>
								<div class="title-sm"><?php the_title(); ?></div>
							</div>
						</a>
						<?php
					endwhile;
				endif;
				wp_reset_postdata();
				?>
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

<?php
get_footer();
