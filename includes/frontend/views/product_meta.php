<?php $product = $GLOBALS['post']; ?>

<div id="product_meta">

    <?php

    $metas = array(
        'description' => array(
            'title'   => __( 'Product Description', 'wp99234' ),
            'content' => get_the_content(),
            'filename' => 'description.php'
        ),
        'tasting' => array(
            'title'   => __( 'Tasting Notes', 'wp99234' ),
            'content' => WP99234()->template->get_var( 'tasting' ),
            'filename' => 'tasting.php'
        ),
        'vintage' =>  array(
            'title'   => __( 'Vintage', 'wp99234' ),
            'content' => WP99234()->template->get_var( 'vintage' ),
            'filename' => 'vintage.php'
        ),
        'prices' => array(
            'title'   => __( 'Prices', 'wp99234' ),
            'content' => WP99234()->template->price_list(true),
            'filename' => 'price.php'
        ),

        'cellar_until' =>  array(
            'title'   => __( 'Cellar Until', 'wp99234' ),
            'content' => WP99234()->template->get_var( 'cellar_until' ),
            'filename' => 'cellar_until.php'
        ),
        'foods' => array(
            'title'   => __( 'Matching Foods', 'wp99234' ),
            'content' => WP99234()->template->get_var( 'foods' ),
            'filename' => 'matching_foods.php'
        ),
        'awards' => array(
            'title'   => __( 'Awards', 'wp99234' ),
            'content' => WP99234()->template->awards_list(),
            'filename' => 'awards.php'
        ),
        'categories' => array(
            'title'   => __( 'Categories', 'wp99234'),
            'content' => get_the_term_list( $product->id, WP99234()->_products->category_taxonomy_name, '', ' - ', '' ),
            'filename' => 'categories.php'
        ),
        'tags' => array(
            'title'   => __( 'Tags', 'wp99234'),
            'content' => get_the_term_list( $product->id, WP99234()->_products->tag_taxonomy_name, '', ' - ', '' ),
            'filename' => 'tags.php'
        ),

    );
    
    foreach( $metas as $key => $meta ){
      if( isset($meta['content']) && ! empty( $meta['content'] ) ) {
        $meta['meta'] = (array)$product;
        $meta['meta']['variety'] = get_post_meta(get_the_ID(), '_variety', true);
        
        # Call the appropriate template file
        echo WP99234()->template->get_template( 'woocommerce/single-product/tabs/troly/'.$meta['filename'], 'product', $meta);
      }
    }
    ?>

</div>