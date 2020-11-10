<?php

// Custom Shortcode For User's Downloads list   . [wooninjas_customer_downloads all_access_customer_downloads_only='yes' ]

function wooninjas_custom_edd_downloads_query( $atts, $content = null ) {
	$atts = shortcode_atts( array(
		'category'         => '',
		'exclude_category' => '',
		'tags'             => '',
		'exclude_tags'     => '',
		'author'           => false,
		'relation'         => 'OR',
		'number'           => 9,
		'price'            => 'no',
		'excerpt'          => 'yes',
		'full_content'     => 'no',
		'buy_button'       => 'yes',
		'columns'          => 3,
		'thumbnails'       => 'true',
		'orderby'          => 'post_date',
		'order'            => 'DESC',
		'ids'              => '',
		'class'            => '',
		'pagination'       => 'true',
	), $atts, 'downloads' );

	$query = array(
		'post_type'      => 'download',
		'orderby'        => $atts['orderby'],
		'order'          => $atts['order']
	);


	if ( filter_var( $atts['pagination'], FILTER_VALIDATE_BOOLEAN ) || ( ! filter_var( $atts['pagination'], FILTER_VALIDATE_BOOLEAN ) && $atts[ 'number' ] ) ) {

		$query['posts_per_page'] = (int) $atts['number'];

		if ( $query['posts_per_page'] < 0 ) {
			$query['posts_per_page'] = abs( $query['posts_per_page'] );
		}
	} else {
		$query['nopaging'] = true;
	}

	if( 'random' == $atts['orderby'] ) {
		$atts['pagination'] = false;
	}

	switch ( $atts['orderby'] ) {
		case 'price':
		$atts['orderby']   = 'meta_value';
		$query['meta_key'] = 'edd_price';
		$query['orderby']  = 'meta_value_num';
		break;

		case 'sales':
		$atts['orderby']   = 'meta_value';
		$query['meta_key'] = '_edd_download_sales';
		$query['orderby']  = 'meta_value_num';
		break;

		case 'earnings':
		$atts['orderby']   = 'meta_value';
		$query['meta_key'] = '_edd_download_earnings';
		$query['orderby']  = 'meta_value_num';
		break;

		case 'title':
		$query['orderby'] = 'title';
		break;

		case 'id':
		$query['orderby'] = 'ID';
		break;

		case 'random':
		$query['orderby'] = 'rand';
		break;

		case 'post__in':
		$query['orderby'] = 'post__in';
		break;

		default:
		$query['orderby'] = 'post_date';
		break;
	}

	if ( $atts['tags'] || $atts['category'] || $atts['exclude_category'] || $atts['exclude_tags'] ) {

		$query['tax_query'] = array(
			'relation' => $atts['relation']
		);

		if ( $atts['tags'] ) {

			$tag_list = explode( ',', $atts['tags'] );

			foreach( $tag_list as $tag ) {

				$t_id  = (int) $tag;
				$is_id = is_int( $t_id ) && ! empty( $t_id );

				if( $is_id ) {

					$term_id = $tag;

				} else {

					$term = get_term_by( 'slug', $tag, 'download_tag' );

					if( ! $term ) {
						continue;
					}

					$term_id = $term->term_id;
				}

				$query['tax_query'][] = array(
					'taxonomy' => 'download_tag',
					'field'    => 'term_id',
					'terms'    => $term_id
				);
			}

		}

		if ( $atts['category'] ) {

			$categories = explode( ',', $atts['category'] );

			foreach( $categories as $category ) {

				$t_id  = (int) $category;
				$is_id = is_int( $t_id ) && ! empty( $t_id );

				if( $is_id ) {

					$term_id = $category;

				} else {

					$term = get_term_by( 'slug', $category, 'download_category' );

					if( ! $term ) {
						continue;
					}

					$term_id = $term->term_id;

				}

				$query['tax_query'][] = array(
					'taxonomy' => 'download_category',
					'field'    => 'term_id',
					'terms'    => $term_id,
				);

			}

		}

		if ( $atts['exclude_category'] ) {

			$categories = explode( ',', $atts['exclude_category'] );

			foreach( $categories as $category ) {

				$t_id  = (int) $category;
				$is_id = is_int( $t_id ) && ! empty( $t_id );

				if( $is_id ) {

					$term_id = $category;

				} else {

					$term = get_term_by( 'slug', $category, 'download_category' );

					if( ! $term ) {
						continue;
					}

					$term_id = $term->term_id;
				}

				$query['tax_query'][] = array(
					'taxonomy' => 'download_category',
					'field'    => 'term_id',
					'terms'    => $term_id,
					'operator' => 'NOT IN'
				);
			}

		}

		if ( $atts['exclude_tags'] ) {

			$tag_list = explode( ',', $atts['exclude_tags'] );

			foreach( $tag_list as $tag ) {

				$t_id  = (int) $tag;
				$is_id = is_int( $t_id ) && ! empty( $t_id );

				if( $is_id ) {

					$term_id = $tag;

				} else {

					$term = get_term_by( 'slug', $tag, 'download_tag' );

					if( ! $term ) {
						continue;
					}

					$term_id = $term->term_id;
				}

				$query['tax_query'][] = array(
					'taxonomy' => 'download_tag',
					'field'    => 'term_id',
					'terms'    => $term_id,
					'operator' => 'NOT IN'
				);

			}

		}
	}

	if ( $atts['exclude_tags'] || $atts['exclude_category'] ) {
		$query['tax_query']['relation'] = 'AND';
	}

	if ( $atts['author'] ) {
		$authors = explode( ',', $atts['author'] );
		if ( ! empty( $authors ) ) {
			$author_ids = array();
			$author_names = array();

			foreach ( $authors as $author ) {
				if ( is_numeric( $author ) ) {
					$author_ids[] = $author;
				} else {
					$user = get_user_by( 'login', $author );
					if ( $user ) {
						$author_ids[] = $user->ID;
					}
				}
			}

			if ( ! empty( $author_ids ) ) {
				$author_ids      = array_unique( array_map( 'absint', $author_ids ) );
				$query['author'] = implode( ',', $author_ids );
			}
		}
	}

	if( ! empty( $atts['ids'] ) )
		$query['post__in'] = explode( ',', $atts['ids'] );

	if ( get_query_var( 'paged' ) )
		$query['paged'] = get_query_var('paged');
	else if ( get_query_var( 'page' ) )
		$query['paged'] = get_query_var( 'page' );
	else
		$query['paged'] = 1;

	// Allow the query to be manipulated by other plugins
	$query = apply_filters( 'edd_downloads_query', $query, $atts );

	$downloads = new WP_Query( $query );


	ob_start();

	if ( $downloads->have_posts() ) :
		$i = 1;
		$columns_class   = array( 'edd_download_columns_' . $atts['columns'] );
		$custom_classes  = array_filter( explode( ',', $atts['class'] ) );
		$wrapper_classes = array_unique( array_merge( $columns_class, $custom_classes ) );
		$wrapper_classes = implode( ' ', $wrapper_classes );
		?>
		<!-- You can Do Custom desgin Here   HTML  -->


		<table class="edd_downloads_list_table">
			<?php while ( $downloads->have_posts() ) : $downloads->the_post(); ?>
				<tr>
					<td>
					<?php if ( function_exists( 'has_post_thumbnail' ) && has_post_thumbnail( get_the_ID() ) ) : ?>
					
						<a href="<?php the_permalink(); ?>">
							<?php echo get_the_post_thumbnail( get_the_ID(), 'thumbnail' ); ?>	
						</a>
					
					<?php endif;?>
					</td>
					<td class="edd_download_title">
						<a itemprop="url" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
					</td>
					<?php if ( ! edd_has_variable_prices( get_the_ID() ) ) : ?>
					<td  class="edd_price">
						<?php edd_price( get_the_ID() ); ?>
					</td>
					<?php endif; ?>
					<td class="edd_download_buy_button">
						<?php echo edd_get_purchase_link( array( 'download_id' => get_the_ID() ) ); ?>				
					</td>
				</tr>
			<?php $i++; endwhile; ?>
			<?php wp_reset_postdata(); ?>
		</table>



		<!--  HTML END HERE  -->
		<?php
		else:
			printf( _x( 'No %s found', 'download post type name', 'easy-digital-downloads' ), edd_get_label_plural() );
	endif;
	do_action( 'edd_downloads_list_after', $atts, $downloads, $query );

	$display = ob_get_clean();

	do_shortcode("[wooninjas_downloads_by_pass_id pass_id=9]");


	return apply_filters( 'downloads_shortcode', $display, $atts, $atts['buy_button'], $atts['columns'], '', $downloads, $atts['excerpt'], $atts['full_content'], $atts['price'], $atts['thumbnails'], $query );
}

add_shortcode( 'wooninjas_customer_downloads', 'wooninjas_custom_edd_downloads_query' );






function wooninjas_downloads_by_pass_id($atts){

	$pass_id= intval($atts['pass_id']);
	$all_included_categories = array();
	$query=$GLOBALS['woonijas_query_var'];

	$edd_all_access_enabled=get_post_meta($pass_id, '_edd_all_access_enabled', true );
	$edd_all_access_exclude=get_post_meta($pass_id, '_edd_all_access_exclude', true );

	
	if ($edd_all_access_enabled) {
		$all_access_settings=get_post_meta($pass_id, '_edd_all_access_settings', true );
		if (!empty($all_access_settings) && !empty($all_access_settings['all_access_categories'])) {

			$all_included_categories= $all_access_settings['all_access_categories'];
			if (!(in_array('all',$all_included_categories))) {
				$query['tax_query'] = array(); 
				$old_tax_query      = array();
				$all_access_tax_query = array();

				
				$all_access_tax_query['tax_query']['relation'] = 'OR';

				
				foreach ( $all_included_categories as $all_access_adjusted_category ) {
					$all_access_tax_query['tax_query'][] = array(
						'taxonomy' => 'download_category',
						'field'    => 'term_id',
						'terms'    => intval($all_access_adjusted_category),
					);	
					
				}

				
				$query['tax_query'] = array(); 
				$query['tax_query']['relation'] = 'AND';
				$query['tax_query'][]           = $all_access_tax_query;

				if ( ! empty( $old_tax_query ) ) {
					$query['tax_query'][] = $old_tax_query;
				}


			}
			

			// Downloads Object List
			$downloads = new WP_Query( $query );
			echo "<pre>";
			print_r($downloads->posts);
			echo "</pre>";
	


		}else{

			echo "No Product In This ALL Access Pass (Not Selected any Category or All Product)";
		}

	}

}

add_shortcode( 'wooninjas_downloads_by_pass_id', 'wooninjas_downloads_by_pass_id');



// Get Query From EDD and EDD ALL ACCESS PASS Addon ,Used In  wooninjas_downloads_by_pass_id Shortcode
add_filter("edd_downloads_query",function($query,$atts){
	
	$GLOBALS['woonijas_query_var'] =$query ;
	return $query;

},999,2);