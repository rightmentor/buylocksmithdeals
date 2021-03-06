<?php
defined('ABSPATH') || exit;
if( !class_exists('BLSDRegionalProductListing') ){
	class BLSDRegionalProductListing{

		protected static $_instance = null;

		/**
		 * provide class instance
		 * @return type
		 */
		public static function instance() {
			if (is_null(self::$_instance)) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Initialize class action and filters.
		 */
		public function __construct() {
			$this->init_hooks();
			
		}

		/**
		 * Filters and Actions are bundled.
		 * @return boolean
		 */
		private function init_hooks() {
			add_action('wp_enqueue_scripts', array( $this, 'blsd_fetch_bootstrap_css_cdn' ) );
			add_action('wp_enqueue_scripts', array( $this, 'blsd_cat_list_style' ) );
			add_shortcode('blsd_product_listing', array($this, 'blsd_product_listing_cb') );
			
			add_filter( 'woocommerce_product_data_store_cpt_get_products_query', array($this, 'handle_custom_query_var'), 10, 2 );
			
		}
		
		public function blsd_cat_list_style(){
			
			/* register select 2 js, start */
			
			$scripts = array(
				'select2'		=>	array(
						'handle'		=>	'blsd-select2-js',
						'src'			=>	BUYLOCKSMITH_DEALS_ASSETS_PATH . 'js/select2'.'.min'.'.js',
						'deps'			=>	array('jquery'),
						'ver'			=>	'1.1',
						'in_footer'		=>	false,
				),
				'blsd-custom'	=>	array(
						'handle'		=>	'blsd-custom-js',
						'src'			=>	BUYLOCKSMITH_DEALS_ASSETS_PATH . 'js/blsd-custom'.''.'.js',
						'deps'			=>	array('blsd-select2-js'),
						'ver'			=>	'1.1',
						'in_footer'		=>	false,
				),
			);
			
			
			wp_register_script( 
				$scripts['select2']['handle'], 
				$scripts['select2']['src'], 
				$scripts['select2']['deps'], 
				$scripts['select2']['ver'], 
				$scripts['select2']['in_footer'] 
			);
			
			wp_register_script( 
				$scripts['blsd-custom']['handle'], 
				$scripts['blsd-custom']['src'], 
				$scripts['blsd-custom']['deps'], 
				$scripts['blsd-custom']['ver'], 
				$scripts['blsd-custom']['in_footer'] 
			);
			/* register select 2 js, stop */
			
			global $post;
			if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'blsd_product_listing')) {
				wp_enqueue_style( 'blsd-cat-list-style', BUYLOCKSMITH_DEALS_ASSETS_PATH . 'css/cat-list-style.css' );
				
				wp_enqueue_script($scripts['select2']['handle']);
				wp_enqueue_script($scripts['blsd-custom']['handle']);
			}
		}
		
		public function blsd_fetch_bootstrap_css_cdn() {
			global $post;
			if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'blsd_product_listing')) {
				wp_enqueue_style( 'blsd-bootstrap-css', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css' );
				wp_enqueue_style( 'blsd-bootstrap-css-theme', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css' );
				wp_enqueue_script( 'blsd-bootstrap-js', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js', array('jquery'), '1.0', false );
			}
		}
		
		public static function blsd_product_listing_cb($atts, $content = ""){
			
			$shortcode_atts_arr = array(
				'att1'		=>	null,
				'att2'		=>	null,
			);
			
			if( isset( $atts['att1'] ) ){
				$shortcode_atts_arr['att1'] = $atts['att1'];
			}
			
			if( isset( $atts['att2'] ) ){
				$shortcode_atts_arr['att2'] = $atts['att2'];
			}
			
			$atts = shortcode_atts( $shortcode_atts_arr, $atts, 'blsd_product_listing' );
			
			/* refresh and update data every time shortcode runs
				table as ID (vendor id), lat (lattitude" and lng (longitude) list.
			*/
			$this->add_refresh_vendor_location_view_table();
			
			$html = "";
			$content = do_shortcode($content);
			
			/* product search form submit handler code, start */
			$form_data = false;
			$blsd_country_alpha2code = '';
			$blsd_regional_product_listing_zip = '';
			$blsd_regional_product_listing_proximity_km = '';
			if( isset($_REQUEST['blsd_regional_product_listing_frm_submit'])){
				$form_data = $_REQUEST;
				$blsd_country_alpha2code = trim($_REQUEST['blsd_regional_product_listing_country']);
				$blsd_regional_product_listing_zip = trim($_REQUEST['blsd_regional_product_listing_zip']);
				$blsd_regional_product_listing_proximity_km = trim($_REQUEST['blsd_regional_product_listing_proximity_km']);
			}
			/* product search form submit handler code, end */
			
			ob_start();
			/* output buffer, start */ ?>
			<div class="blsd_product_listing" id="blsd_product_listing">
			
				<!--div class="jumbotron text-center">
				  <h1>Categories</h1>
				  <p>Go for unending domains of shopping</p>
				</div-->

				<div class="container-fluid blsd_cat_list_container">
				 
				  <form name="blsd_regional_product_listing_frm" id="blsd_regional_product_listing_frm" class="blsd_regional_product_listing_frm" action="" method="post">
					<?php
                                        if(!isset($_REQUEST['cat'])){
                                        ?>
                                      <div class="row blsd_cat_list_row">
						<div class="col-sm-2 blsd_cat_list_column">
						  <p>
							<select id="blsd_regional_product_listing_country" class="blsd_regional_product_listing_country" placeholder="Choose Country" name="blsd_regional_product_listing_country" required="required">
							<option></option>
							<?php $country_list = $this->get_country_arr(); ?>
							<?php foreach( $country_list as $cv ){ ?>
								<option value="<?php echo urlencode(utf8_encode($cv['alpha2Code'])); ?>" <?php echo selected($cv['alpha2Code'], $blsd_country_alpha2code); ?>>
								<?php echo utf8_encode($cv['name']); ?>
								</option>
							<?php } ?>
							</select>
							
						  </p>
						</div>
						
						<div class="col-sm-3 blsd_cat_list_column">
						  <p>
							<input type="text" id="blsd_regional_product_listing_zip" class="blsd_regional_product_listing_zip" value="<?php echo $blsd_regional_product_listing_zip; ?>" placeholder="ZIP/Postal Code" name="blsd_regional_product_listing_zip" required="required" />
						  </p>
						</div>
						
						<div class="col-sm-3 blsd_cat_list_column">
						  <p>
							<input type="number" step="0.01" min="0" id="blsd_regional_product_listing_proximity_km" class="blsd_regional_product_listing_proximity_km" value="<?php echo $blsd_regional_product_listing_proximity_km; ?>" placeholder="Nearby (in Km)" name="blsd_regional_product_listing_proximity_km" />
						  </p>
						</div>
						
						<div class="col-sm-2 blsd_cat_list_column">
						  <p>
							<input type="submit" id="blsd_regional_product_listing_frm_submit" class="blsd_regional_product_listing_frm_submit" name="blsd_regional_product_listing_frm_submit" value="Search" />
						  </p>
						</div>
						<div class="col-sm-2 blsd_cat_list_column">
						  <p>
							<a href="<?php echo get_permalink(); ?>" class="btn btn-primary">Clear filter</a>
						  </p>
						</div>
					  </div>
					  
					  <?php 
                                        }
                                          $product_arr = $this->get_product_listing(); ?>
						  <?php
						
						?>
					  
					  <div class="row blsd_cat_list_row">
					  <?php if( $product_arr ){ foreach($product_arr as $p){ ?>
						<div class="col-sm-4 blsd_cat_list_column">
						  <p>
							<a href="<?php echo $p['permalink']; ?>">
								<img src="<?php echo $p['featured_image']['large']['url']; ?>" style="width: 300px; height: 210px;" />
							</a>
						  </p>
						  <p>
							<a href="<?php echo $p['permalink']; ?>">
								<?php echo $p['name']; ?>
							</a>
						  </p>
						  <p>
							<strong>Product ID: </strong><?php echo $p['id']; ?>
						  </p>
						  <p>
							<strong>Price: </strong><?php echo $p['price_html']; ?>
						  </p>
						  <p>
							<strong>Vendor: </strong><a href="<?php echo $p['vendor_permalink']; ?>"><?php echo $p['vendor_display_name']; ?></a>
						  </p>
                                                  <?php if($p['vendor_short_formatted_address']!=''){?>
						  <p>
							<strong>Vendor Address: </strong><?php echo $p['vendor_short_formatted_address']; ?>
						  </p>
                                                  <?php  } ?>
						</div>
					  <?php } } else { ?>
					  <div class="col-sm blsd_cat_list_column">
						  <p>
							No product found.
						  </p>
						</div>
					  <?php } ?>
					  </div>
				  </form> <!-- form end -->
				</div> <!-- container end -->
			</div>
			<?php
			/* output buffer, end */
			$html = ob_get_clean();

			return $html;
			
		}
		
		public function get_product_listing(){
                     $BuyLockSmithDealsCustomizationAddon = new BuyLockSmithDealsCustomizationAddon;
                                                        $BuyLockSmithDealsCustomizationAddon->blsd_update_user_country();
			$param = array();
			$products = false;
			$authors = $this->get_vendor_ids();
			if( isset( $_REQUEST['blsd_regional_product_listing_frm_submit'] ) || isset( $_REQUEST['blsd_regional_product_listing_proximity_km'] ) ){
				$param['author__in'] = $authors;
				if( empty( $param['author__in'] ) ){
					return false;
				}
			}
			
			$param['status'] = 'publish';
			/*$param['return'] = 'ids';*/
			$param['posts_per_page'] = '-1';
                        if(isset($_REQUEST['cat'])){
                            if($_REQUEST['cat']!=''){
			$param['product_cat'] = $_REQUEST['cat'];
                            }
                        }
			$t_products = wc_get_products($param);
			
			if( $t_products ){
				if( is_array($t_products) ){
					if( !empty($t_products) ){
						$products = $t_products;
						$products = $this->get_product_details_arr($products);
					}
				}
			}
			
			return $products;
			
		}
		
		public function get_product_details_arr($products){
			$tp = $products;
			$p = array();
			foreach( $tp as $k => $v ){
				$product = array();
				$product['ID'] = $v->get_id();
				$product['id'] = $product['ID'];
				$product['type'] = $v->get_type();
				$product['name'] = $v->get_name();
				$product['slug'] = $v->get_slug();
				$product['date_created'] = $v->get_date_created();
				$product['date_modified'] = $v->get_date_modified();
				$product['status'] = $v->get_status();
				$product['featured'] = $v->get_featured();
				$product['catalog_visibility'] = $v->get_catalog_visibility();
				$product['description'] = $v->get_description();
				$product['short_description'] = $v->get_short_description();
				$product['sku'] = $v->get_sku();
				$product['price'] = $v->get_price();
				$product['regular_price'] = $v->get_regular_price();
				$product['sale_price'] = $v->get_sale_price();
				$product['date_on_sale_from'] = $v->get_date_on_sale_from();
				$product['date_on_sale_to'] = $v->get_date_on_sale_to();
				$product['total_sales'] = $v->get_total_sales();
				$product['manage_stock'] = $v->get_manage_stock();
				$product['stock_quantity'] = $v->get_stock_quantity();
				$product['stock_status'] = $v->get_stock_status();
				$product['backorders'] = $v->get_backorders();
				$product['low_stock_amount'] = $v->get_low_stock_amount();
				$product['sold_individually'] = $v->get_sold_individually();
				$product['category_ids'] = $v->get_category_ids();
				$product['tag_ids'] = $v->get_tag_ids();
				$product['virtual'] = $v->get_virtual();
				$product['gallery_image_ids'] = $v->get_gallery_image_ids();
				$product['shipping_class_id'] = $v->get_shipping_class_id();
				$product['downloads'] = $v->get_downloads();
				$product['download_expiry'] = $v->get_download_expiry();
				$product['downloadable'] = $v->get_downloadable();
				$product['download_limit'] = $v->get_download_limit();
				$product['image_id'] = $v->get_image_id();
				$product['rating_counts'] = $v->get_rating_counts();
				$product['average_rating'] = $v->get_average_rating();
				$product['review_count'] = $v->get_review_count();
				$product['title'] = $v->get_title();
				$product['permalink'] = $v->get_permalink(); 
				$product['get_children'] = $v->get_children();
				$product['stock_managed_by_id'] = $v->get_stock_managed_by_id();
				$product['price_html'] = $v->get_price_html();
				$product['formatted_name'] = $v->get_formatted_name();
				$product['min_purchase_quantity'] = $v->get_min_purchase_quantity();
				$product['max_purchase_quantity'] = $v->get_max_purchase_quantity();
				//$product['add_to_cart_url'] = $v->add_to_cart_url();
				//$product['single_add_to_cart_text'] = $v->single_add_to_cart_text();
				//$product['add_to_cart_text'] = $v->add_to_cart_text();
				$product['add_to_cart_description'] = $v->add_to_cart_description();
				$product['image'] = $v->get_image();
				$product['shipping_class'] = $v->get_shipping_class();
				$product['rating_count'] = $v->get_rating_count();
				$product['file'] = $v->get_file();
				$product['price_suffix'] = $v->get_price_suffix();
				$product['availability'] = $v->get_availability();
				
				$fimg_arr = array(
							'url'				=>	BUYLOCKSMITH_DEALS_ASSETS_PATH . 'img/no_image_available.jpeg',
							'width'				=>	1200,
							'height'			=>	1200,
							'is_intermediate'	=>	null,
						);
					
				$product['featured_image']['thumbnail'] = $fimg_arr;
				$product['featured_image']['medium'] = $fimg_arr;
				$product['featured_image']['large'] = $fimg_arr;
				
				if( $product['image_id'] ){
					
					$fimg = wp_get_attachment_image_src( $product['image_id'], 'thumbnail');
					if( $fimg ){
						$fimg_arr = array(
							'url'				=>	$fimg[0],
							'width'				=>	$fimg[1],
							'height'			=>	$fimg[2],
							'is_intermediate'	=>	$fimg[3],
						);
						$product['featured_image']['thumbnail'] = $fimg_arr;
					}
					
					$fimg = wp_get_attachment_image_src( $product['image_id'], 'medium');
					if( $fimg ){
						$fimg_arr = array(
							'url'				=>	$fimg[0],
							'width'				=>	$fimg[1],
							'height'			=>	$fimg[2],
							'is_intermediate'	=>	$fimg[3],
						);
						$product['featured_image']['medium'] = $fimg_arr;
					}
					
					$fimg = wp_get_attachment_image_src( $product['image_id'], 'large');
					if( $fimg ){
						$fimg_arr = array(
							'url'				=>	$fimg[0],
							'width'				=>	$fimg[1],
							'height'			=>	$fimg[2],
							'is_intermediate'	=>	$fimg[3],
						);
						$product['featured_image']['large'] = $fimg_arr;
					}
				}
				
				$product['vendor'] = '';
				$product['vendor_permalink'] = '';
				$product['vendor_display_name'] = '';
				$product['vendor_formatted_address'] = '';
				$vendor = get_wcmp_product_vendors( $product['id'] );
				if( $vendor ) {
					$term_vendor = wp_get_post_terms( $product['id'],  'dc_vendor_shop' );
					$product['vendor'] = $vendor;
					$product['vendor_permalink'] = $vendor->permalink;
					$product['vendor_display_name'] = $vendor->user_data->display_name;
					$product['vendor_formatted_address'] = $vendor->get_formatted_address();
                                        
                                        
                                           
                                              $city = get_user_meta($vendor->id, '_vendor_city', true);
                                              $state = get_user_meta($vendor->id, '_vendor_state', true);
                                              $postcode = get_user_meta($vendor->id, '_vendor_postcode', true);
                                              $address = $city?$city:'';
                                              $address = $state?$address.' '.$state:'';
                                           $product['vendor_short_formatted_address'] =   $address = $postcode?$address.' '.$postcode:'';
				}
				
				$p[] = $product;
			}
			
			return $p;
		}
		
		public function get_vendor_ids(){
			$vendors_ids = array();
			$lat_long = $this->get_latlong_by_country_and_postal_code();
			
			if( $lat_long ){
				$lat = $lat_long['lat'];
				$lng = $lat_long['lng'];
				
				$nearby_vendors = $this->get_nearby_vendors($lat, $lng);
				foreach($nearby_vendors as $k => $v){
					$vendors_ids[] = $v['vendor_id'];
				}
			}
			return $vendors_ids;
		}
		
		public function get_vendor_product($vendor_id){
			/* method not in use */
			$vendor = get_wcmp_vendor($vendor_id);
			$vendor_products = $vendor->get_products(array('fields' => 'ids'));
		}
		
		public function handle_custom_query_var( $query, $query_vars ){
			if ( ! empty( $query_vars['customvar'] ) ) {
				$query['meta_query'][] = array(
					'key' => 'customvar',
					'value' => esc_attr( $query_vars['customvar'] ),
				);
			}
			
			return $query;
		}
		
		public function get_nearby_vendors( $lat , $lng ){
			
			$servername = DB_HOST;
			$username = DB_USER;
			$password = DB_PASSWORD;
			$dbname = DB_NAME;
			
			$conn = new mysqli($servername, $username, $password, $dbname);
			mysqli_set_charset($conn,"utf8");
			
			if ($conn->connect_error) {
				die("Connection failed: " . $conn->connect_error);
			}
			
			/* Hilton Hotel Geejgarh Vihar */
			$lat = $lat; /* 26.901313; */
			$lon = $lng; /* 75.783794; */
			
			/* Only show places within 5km by default */
			$distance = 5;
			if( isset( $_REQUEST['blsd_regional_product_listing_proximity_km'] ) ){
				$t_distance = (float) $_REQUEST['blsd_regional_product_listing_proximity_km'];
				$distance = $t_distance;
			}
			
			$lat = mysqli_real_escape_string($conn, $lat);
			$lon = mysqli_real_escape_string($conn, $lon);
			$distance = mysqli_real_escape_string($conn, $distance);
			
			$view_table_name = $this->get_table_name();
			
			$query = <<<EOF
				SELECT * FROM (
					SELECT *, 
					(
						(
							(
								acos(
									sin(( $lat * pi() / 180))
									*
									sin(( `lat` * pi() / 180)) + cos(( $lat * pi() /180 ))
									*
									cos(( `lat` * pi() / 180)) * cos((( $lon - `lng`) * pi()/180)))
							) * 180/pi()
						) * 60 * 1.1515 * 1.609344
					)
					as distance FROM `$view_table_name`  	
				) $view_table_name 
				WHERE distance <= $distance
				ORDER BY distance ASC;
EOF;
			/* echo $query; */
			$result = $conn->query($query);
			$vendors_arr = array();
			if ($result->num_rows > 0) {
				/* output data of each row */
				while($row = $result->fetch_assoc()) {
					$varr = array(
						'vendor_id'	=>	$row['ID'],
						'lat'		=>	$row['lat'],
						'lng'		=>	$row['lng'],
						'distance'	=>	$row['distance'],
					);
					
					$vendors_arr[] = $varr;
				}
			}
                      
			return $vendors_arr;
			
		}
		
		public function add_refresh_vendor_location_view_table(){
			global $wpdb;
			$wpprefix = $wpdb->prefix;
			$view_table_name = $this->get_table_name();
			
			$sql	=	"CREATE OR REPLACE VIEW $view_table_name AS 
							SELECT
								u1.ID,
								m1.meta_value AS lat,
								m2.meta_value AS lng
							FROM ".$wpprefix."users u1
							JOIN ".$wpprefix."usermeta m1 ON (m1.user_id = u1.id AND m1.meta_key = '_store_lat')
							JOIN ".$wpprefix."usermeta m2 ON (m2.user_id = u1.id AND m2.meta_key = '_store_lng')";
			
			$wpdb->query($sql);
		}
		
		public function get_table_name(){
			global $wpdb;
			$wpprefix = $wpdb->prefix;
			$view_table_name = $wpprefix . 'blsd_vendor_markers_view';
			return $view_table_name;
		}
		
		public function get_latlong_by_country_and_postal_code(){
			$country_code = false;
			$postal_code = false;
			$lat_long = false;
			
			if( isset( $_REQUEST['blsd_regional_product_listing_frm_submit'] ) || isset($_REQUEST['blsd_regional_product_listing_zip']) ){
				
				if( isset( $_REQUEST['blsd_regional_product_listing_country'] ) ){ 
					$t_country_code = trim($_REQUEST['blsd_regional_product_listing_country']);
					$country_code = $t_country_code;
				}
				
				if( isset( $_REQUEST['blsd_regional_product_listing_zip'] ) ){
					$t_postal_code = trim($_REQUEST['blsd_regional_product_listing_zip']);
					$postal_code = $t_postal_code;
				}
			}
			
			if( $country_code && $postal_code ){
				
				$key = $this->get_google_map_api_key();
				$address = $postal_code . ',' . $country_code;
				
				$geocode_url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.$address.'&key='.$key;
				
				$output = file_get_contents($geocode_url);
				
				if($output){
					$output = json_decode($output,true);
					
					if( 'OK' == $output['status'] ){
						
						$t_lat = $output['results'][0]['geometry']['location']['lat'];
						$t_lng = $output['results'][0]['geometry']['location']['lng'];
						
						if( '' != $t_lat && '' != $t_lng ){
							$lat_long = array(
								'lat'	=>	$t_lat,
								'lng'	=>	$t_lng,
							);
						}
					}
				}
			}
			
			return $lat_long;
		}
		
		public function get_google_map_api_key(){
			$key = 'AIzaSyCuC3PEVYk9RGDIIuLM1ur-sQ7y73ff3eY';
			return $key;
		}
		
		public function get_country_arr(){
			$country = array(
  array('ID' => '1','name' => 'Afghanistan','slug' => 'afghanistan','alpha2Code' => 'AF','alpha3Code' => 'AFG','callingCodes' => '93','capital' => 'Kabul','region' => 'Asia','subregion' => 'Southern Asia','population' => '27657145','latlng' => '33,65','demonym' => 'Afghan','timezones' => 'UTC+04:30','currency_name' => 'Afghan afghani','currency_code' => 'AFN','currency_symbol' => '?','flag' => 'https://restcountries.eu/data/afg.svg','address_format' => ''),
  array('ID' => '2','name' => '�land Islands','slug' => 'aland-islands','alpha2Code' => 'AX','alpha3Code' => 'ALA','callingCodes' => '358','capital' => 'Mariehamn','region' => 'Europe','subregion' => 'Northern Europe','population' => '28875','latlng' => '60.116667,19.9','demonym' => '�landish','timezones' => 'UTC+02:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/ala.svg','address_format' => ''),
  array('ID' => '3','name' => 'Albania','slug' => 'albania','alpha2Code' => 'AL','alpha3Code' => 'ALB','callingCodes' => '355','capital' => 'Tirana','region' => 'Europe','subregion' => 'Southern Europe','population' => '2886026','latlng' => '41,20','demonym' => 'Albanian','timezones' => 'UTC+01:00','currency_name' => 'Albanian lek','currency_code' => 'ALL','currency_symbol' => 'L','flag' => 'https://restcountries.eu/data/alb.svg','address_format' => ''),
  array('ID' => '4','name' => 'Algeria','slug' => 'algeria','alpha2Code' => 'DZ','alpha3Code' => 'DZA','callingCodes' => '213','capital' => 'Algiers','region' => 'Africa','subregion' => 'Northern Africa','population' => '40400000','latlng' => '28,3','demonym' => 'Algerian','timezones' => 'UTC+01:00','currency_name' => 'Algerian dinar','currency_code' => 'DZD','currency_symbol' => '?.?','flag' => 'https://restcountries.eu/data/dza.svg','address_format' => ''),
  array('ID' => '5','name' => 'American Samoa','slug' => 'american-samoa','alpha2Code' => 'AS','alpha3Code' => 'ASM','callingCodes' => '1684','capital' => 'Pago Pago','region' => 'Oceania','subregion' => 'Polynesia','population' => '57100','latlng' => '-14.33333333,-170','demonym' => 'American Samoan','timezones' => 'UTC-11:00','currency_name' => 'United State Dollar','currency_code' => 'USD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/asm.svg','address_format' => ''),
  array('ID' => '6','name' => 'Andorra','slug' => 'andorra','alpha2Code' => 'AD','alpha3Code' => 'AND','callingCodes' => '376','capital' => 'Andorra la Vella','region' => 'Europe','subregion' => 'Southern Europe','population' => '78014','latlng' => '42.5,1.5','demonym' => 'Andorran','timezones' => 'UTC+01:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/and.svg','address_format' => ''),
  array('ID' => '7','name' => 'Angola','slug' => 'angola','alpha2Code' => 'AO','alpha3Code' => 'AGO','callingCodes' => '244','capital' => 'Luanda','region' => 'Africa','subregion' => 'Middle Africa','population' => '25868000','latlng' => '-12.5,18.5','demonym' => 'Angolan','timezones' => 'UTC+01:00','currency_name' => 'Angolan kwanza','currency_code' => 'AOA','currency_symbol' => 'Kz','flag' => 'https://restcountries.eu/data/ago.svg','address_format' => ''),
  array('ID' => '8','name' => 'Anguilla','slug' => 'anguilla','alpha2Code' => 'AI','alpha3Code' => 'AIA','callingCodes' => '1264','capital' => 'The Valley','region' => 'Americas','subregion' => 'Caribbean','population' => '13452','latlng' => '18.25,-63.16666666','demonym' => 'Anguillian','timezones' => 'UTC-04:00','currency_name' => 'East Caribbean dollar','currency_code' => 'XCD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/aia.svg','address_format' => ''),
  array('ID' => '9','name' => 'Antarctica','slug' => 'antarctica','alpha2Code' => 'AQ','alpha3Code' => 'ATA','callingCodes' => '672','capital' => '','region' => 'Polar','subregion' => '','population' => '1000','latlng' => '-74.65,4.48','demonym' => '','timezones' => 'UTC-03:00,UTC+03:00,UTC+05:00,UTC+06:00,UTC+07:00,UTC+08:00,UTC+10:00,UTC+12:00','currency_name' => 'Australian dollar','currency_code' => 'AUD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/ata.svg','address_format' => ''),
  array('ID' => '10','name' => 'Antigua and Barbuda','slug' => 'antigua-and-barbuda','alpha2Code' => 'AG','alpha3Code' => 'ATG','callingCodes' => '1268','capital' => 'Saint John\'s','region' => 'Americas','subregion' => 'Caribbean','population' => '86295','latlng' => '17.05,-61.8','demonym' => 'Antiguan, Barbudan','timezones' => 'UTC-04:00','currency_name' => 'East Caribbean dollar','currency_code' => 'XCD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/atg.svg','address_format' => ''),
  array('ID' => '11','name' => 'Argentina','slug' => 'argentina','alpha2Code' => 'AR','alpha3Code' => 'ARG','callingCodes' => '54','capital' => 'Buenos Aires','region' => 'Americas','subregion' => 'South America','population' => '43590400','latlng' => '-34,-64','demonym' => 'Argentinean','timezones' => 'UTC-03:00','currency_name' => 'Argentine peso','currency_code' => 'ARS','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/arg.svg','address_format' => ''),
  array('ID' => '12','name' => 'Armenia','slug' => 'armenia','alpha2Code' => 'AM','alpha3Code' => 'ARM','callingCodes' => '374','capital' => 'Yerevan','region' => 'Asia','subregion' => 'Western Asia','population' => '2994400','latlng' => '40,45','demonym' => 'Armenian','timezones' => 'UTC+04:00','currency_name' => 'Armenian dram','currency_code' => 'AMD','currency_symbol' => '','flag' => 'https://restcountries.eu/data/arm.svg','address_format' => ''),
  array('ID' => '13','name' => 'Aruba','slug' => 'aruba','alpha2Code' => 'AW','alpha3Code' => 'ABW','callingCodes' => '297','capital' => 'Oranjestad','region' => 'Americas','subregion' => 'Caribbean','population' => '107394','latlng' => '12.5,-69.96666666','demonym' => 'Aruban','timezones' => 'UTC-04:00','currency_name' => 'Aruban florin','currency_code' => 'AWG','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/abw.svg','address_format' => ''),
  array('ID' => '14','name' => 'Australia','slug' => 'australia','alpha2Code' => 'AU','alpha3Code' => 'AUS','callingCodes' => '61','capital' => 'Canberra','region' => 'Oceania','subregion' => 'Australia and New Zealand','population' => '24117360','latlng' => '-27,133','demonym' => 'Australian','timezones' => 'UTC+05:00,UTC+06:30,UTC+07:00,UTC+08:00,UTC+09:30,UTC+10:00,UTC+10:30,UTC+11:30','currency_name' => 'Australian dollar','currency_code' => 'AUD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/aus.svg','address_format' => ''),
  array('ID' => '15','name' => 'Austria','slug' => 'austria','alpha2Code' => 'AT','alpha3Code' => 'AUT','callingCodes' => '43','capital' => 'Vienna','region' => 'Europe','subregion' => 'Western Europe','population' => '8725931','latlng' => '47.33333333,13.33333333','demonym' => 'Austrian','timezones' => 'UTC+01:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/aut.svg','address_format' => ''),
  array('ID' => '16','name' => 'Azerbaijan','slug' => 'azerbaijan','alpha2Code' => 'AZ','alpha3Code' => 'AZE','callingCodes' => '994','capital' => 'Baku','region' => 'Asia','subregion' => 'Western Asia','population' => '9730500','latlng' => '40.5,47.5','demonym' => 'Azerbaijani','timezones' => 'UTC+04:00','currency_name' => 'Azerbaijani manat','currency_code' => 'AZN','currency_symbol' => '','flag' => 'https://restcountries.eu/data/aze.svg','address_format' => ''),
  array('ID' => '17','name' => 'Bahamas','slug' => 'bahamas','alpha2Code' => 'BS','alpha3Code' => 'BHS','callingCodes' => '1242','capital' => 'Nassau','region' => 'Americas','subregion' => 'Caribbean','population' => '378040','latlng' => '24.25,-76','demonym' => 'Bahamian','timezones' => 'UTC-05:00','currency_name' => 'Bahamian dollar','currency_code' => 'BSD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/bhs.svg','address_format' => ''),
  array('ID' => '18','name' => 'Bahrain','slug' => 'bahrain','alpha2Code' => 'BH','alpha3Code' => 'BHR','callingCodes' => '973','capital' => 'Manama','region' => 'Asia','subregion' => 'Western Asia','population' => '1404900','latlng' => '26,50.55','demonym' => 'Bahraini','timezones' => 'UTC+03:00','currency_name' => 'Bahraini dinar','currency_code' => 'BHD','currency_symbol' => '.?.','flag' => 'https://restcountries.eu/data/bhr.svg','address_format' => ''),
  array('ID' => '19','name' => 'Bangladesh','slug' => 'bangladesh','alpha2Code' => 'BD','alpha3Code' => 'BGD','callingCodes' => '880','capital' => 'Dhaka','region' => 'Asia','subregion' => 'Southern Asia','population' => '161006790','latlng' => '24,90','demonym' => 'Bangladeshi','timezones' => 'UTC+06:00','currency_name' => 'Bangladeshi taka','currency_code' => 'BDT','currency_symbol' => '?','flag' => 'https://restcountries.eu/data/bgd.svg','address_format' => ''),
  array('ID' => '20','name' => 'Barbados','slug' => 'barbados','alpha2Code' => 'BB','alpha3Code' => 'BRB','callingCodes' => '1246','capital' => 'Bridgetown','region' => 'Americas','subregion' => 'Caribbean','population' => '285000','latlng' => '13.16666666,-59.53333333','demonym' => 'Barbadian','timezones' => 'UTC-04:00','currency_name' => 'Barbadian dollar','currency_code' => 'BBD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/brb.svg','address_format' => ''),
  array('ID' => '21','name' => 'Belarus','slug' => 'belarus','alpha2Code' => 'BY','alpha3Code' => 'BLR','callingCodes' => '375','capital' => 'Minsk','region' => 'Europe','subregion' => 'Eastern Europe','population' => '9498700','latlng' => '53,28','demonym' => 'Belarusian','timezones' => 'UTC+03:00','currency_name' => 'New Belarusian ruble','currency_code' => 'BYN','currency_symbol' => 'Br','flag' => 'https://restcountries.eu/data/blr.svg','address_format' => ''),
  array('ID' => '22','name' => 'Belgium','slug' => 'belgium','alpha2Code' => 'BE','alpha3Code' => 'BEL','callingCodes' => '32','capital' => 'Brussels','region' => 'Europe','subregion' => 'Western Europe','population' => '11319511','latlng' => '50.83333333,4','demonym' => 'Belgian','timezones' => 'UTC+01:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/bel.svg','address_format' => ''),
  array('ID' => '23','name' => 'Belize','slug' => 'belize','alpha2Code' => 'BZ','alpha3Code' => 'BLZ','callingCodes' => '501','capital' => 'Belmopan','region' => 'Americas','subregion' => 'Central America','population' => '370300','latlng' => '17.25,-88.75','demonym' => 'Belizean','timezones' => 'UTC-06:00','currency_name' => 'Belize dollar','currency_code' => 'BZD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/blz.svg','address_format' => ''),
  array('ID' => '24','name' => 'Benin','slug' => 'benin','alpha2Code' => 'BJ','alpha3Code' => 'BEN','callingCodes' => '229','capital' => 'Porto-Novo','region' => 'Africa','subregion' => 'Western Africa','population' => '10653654','latlng' => '9.5,2.25','demonym' => 'Beninese','timezones' => 'UTC+01:00','currency_name' => 'West African CFA franc','currency_code' => 'XOF','currency_symbol' => 'Fr','flag' => 'https://restcountries.eu/data/ben.svg','address_format' => ''),
  array('ID' => '25','name' => 'Bermuda','slug' => 'bermuda','alpha2Code' => 'BM','alpha3Code' => 'BMU','callingCodes' => '1441','capital' => 'Hamilton','region' => 'Americas','subregion' => 'Northern America','population' => '61954','latlng' => '32.33333333,-64.75','demonym' => 'Bermudian','timezones' => 'UTC-04:00','currency_name' => 'Bermudian dollar','currency_code' => 'BMD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/bmu.svg','address_format' => ''),
  array('ID' => '26','name' => 'Bhutan','slug' => 'bhutan','alpha2Code' => 'BT','alpha3Code' => 'BTN','callingCodes' => '975','capital' => 'Thimphu','region' => 'Asia','subregion' => 'Southern Asia','population' => '775620','latlng' => '27.5,90.5','demonym' => 'Bhutanese','timezones' => 'UTC+06:00','currency_name' => 'Bhutanese ngultrum','currency_code' => 'BTN','currency_symbol' => 'Nu.','flag' => 'https://restcountries.eu/data/btn.svg','address_format' => ''),
  array('ID' => '27','name' => 'Bolivia','slug' => 'bolivia','alpha2Code' => 'BO','alpha3Code' => 'BOL','callingCodes' => '591','capital' => 'Sucre','region' => 'Americas','subregion' => 'South America','population' => '10985059','latlng' => '-17,-65','demonym' => 'Bolivian','timezones' => 'UTC-04:00','currency_name' => 'Bolivian boliviano','currency_code' => 'BOB','currency_symbol' => 'Bs.','flag' => 'https://restcountries.eu/data/bol.svg','address_format' => ''),
  array('ID' => '28','name' => 'Bonaire, Sint Eustatius and Saba','slug' => 'bonaire-sint-eustatius-and-saba','alpha2Code' => 'BQ','alpha3Code' => 'BES','callingCodes' => '5997','capital' => 'Kralendijk','region' => 'Americas','subregion' => 'Caribbean','population' => '17408','latlng' => '12.15,-68.266667','demonym' => 'Dutch','timezones' => 'UTC-04:00','currency_name' => 'United States dollar','currency_code' => 'USD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/bes.svg','address_format' => ''),
  array('ID' => '29','name' => 'Bosnia and Herzegovina','slug' => 'bosnia-and-herzegovina','alpha2Code' => 'BA','alpha3Code' => 'BIH','callingCodes' => '387','capital' => 'Sarajevo','region' => 'Europe','subregion' => 'Southern Europe','population' => '3531159','latlng' => '44,18','demonym' => 'Bosnian, Herzegovinian','timezones' => 'UTC+01:00','currency_name' => 'Bosnia and Herzegovina convertible mark','currency_code' => 'BAM','currency_symbol' => '','flag' => 'https://restcountries.eu/data/bih.svg','address_format' => ''),
  array('ID' => '30','name' => 'Botswana','slug' => 'botswana','alpha2Code' => 'BW','alpha3Code' => 'BWA','callingCodes' => '267','capital' => 'Gaborone','region' => 'Africa','subregion' => 'Southern Africa','population' => '2141206','latlng' => '-22,24','demonym' => 'Motswana','timezones' => 'UTC+02:00','currency_name' => 'Botswana pula','currency_code' => 'BWP','currency_symbol' => 'P','flag' => 'https://restcountries.eu/data/bwa.svg','address_format' => ''),
  array('ID' => '31','name' => 'Bouvet Island','slug' => 'bouvet-island','alpha2Code' => 'BV','alpha3Code' => 'BVT','callingCodes' => '','capital' => '','region' => '','subregion' => '','population' => '0','latlng' => '-54.43333333,3.4','demonym' => '','timezones' => 'UTC+01:00','currency_name' => 'Norwegian krone','currency_code' => 'NOK','currency_symbol' => 'kr','flag' => 'https://restcountries.eu/data/bvt.svg','address_format' => ''),
  array('ID' => '32','name' => 'Brazil','slug' => 'brazil','alpha2Code' => 'BR','alpha3Code' => 'BRA','callingCodes' => '55','capital' => 'Bras�lia','region' => 'Americas','subregion' => 'South America','population' => '206135893','latlng' => '-10,-55','demonym' => 'Brazilian','timezones' => 'UTC-05:00,UTC-04:00,UTC-03:00,UTC-02:00','currency_name' => 'Brazilian real','currency_code' => 'BRL','currency_symbol' => 'R$','flag' => 'https://restcountries.eu/data/bra.svg','address_format' => ''),
  array('ID' => '33','name' => 'British Indian Ocean Territory','slug' => 'british-indian-ocean-territory','alpha2Code' => 'IO','alpha3Code' => 'IOT','callingCodes' => '246','capital' => 'Diego Garcia','region' => 'Africa','subregion' => 'Eastern Africa','population' => '3000','latlng' => '-6,71.5','demonym' => 'Indian','timezones' => 'UTC+06:00','currency_name' => 'United States dollar','currency_code' => 'USD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/iot.svg','address_format' => ''),
  array('ID' => '34','name' => 'United States Minor Outlying Islands','slug' => 'united-states-minor-outlying-islands','alpha2Code' => 'UM','alpha3Code' => 'UMI','callingCodes' => '','capital' => '','region' => 'Americas','subregion' => 'Northern America','population' => '300','latlng' => '','demonym' => 'American','timezones' => 'UTC-11:00,UTC-10:00,UTC+12:00','currency_name' => 'United States Dollar','currency_code' => 'USD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/umi.svg','address_format' => ''),
  array('ID' => '35','name' => 'British Virgin Islands','slug' => 'british-virgin-islands','alpha2Code' => 'VG','alpha3Code' => 'VGB','callingCodes' => '1284','capital' => 'Road Town','region' => 'Americas','subregion' => 'Caribbean','population' => '28514','latlng' => '18.431383,-64.62305','demonym' => 'Virgin Islander','timezones' => 'UTC-04:00','currency_name' => '[D]','currency_code' => '','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/vgb.svg','address_format' => ''),
  array('ID' => '36','name' => 'US Virgin Islands','slug' => 'us-virgin-islands','alpha2Code' => 'VI','alpha3Code' => 'VIR','callingCodes' => '1 340','capital' => 'Charlotte Amalie','region' => 'Americas','subregion' => 'Caribbean','population' => '114743','latlng' => '18.34,-64.93','demonym' => 'Virgin Islander','timezones' => 'UTC-04:00','currency_name' => 'United States dollar','currency_code' => 'USD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/vir.svg','address_format' => ''),
  array('ID' => '37','name' => 'Brunei Darussalam','slug' => 'brunei-darussalam','alpha2Code' => 'BN','alpha3Code' => 'BRN','callingCodes' => '673','capital' => 'Bandar Seri Begawan','region' => 'Asia','subregion' => 'South-Eastern Asia','population' => '411900','latlng' => '4.5,114.66666666','demonym' => 'Bruneian','timezones' => 'UTC+08:00','currency_name' => 'Brunei dollar','currency_code' => 'BND','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/brn.svg','address_format' => ''),
  array('ID' => '38','name' => 'Bulgaria','slug' => 'bulgaria','alpha2Code' => 'BG','alpha3Code' => 'BGR','callingCodes' => '359','capital' => 'Sofia','region' => 'Europe','subregion' => 'Eastern Europe','population' => '7153784','latlng' => '43,25','demonym' => 'Bulgarian','timezones' => 'UTC+02:00','currency_name' => 'Bulgarian lev','currency_code' => 'BGN','currency_symbol' => '??','flag' => 'https://restcountries.eu/data/bgr.svg','address_format' => ''),
  array('ID' => '39','name' => 'Burkina Faso','slug' => 'burkina-faso','alpha2Code' => 'BF','alpha3Code' => 'BFA','callingCodes' => '226','capital' => 'Ouagadougou','region' => 'Africa','subregion' => 'Western Africa','population' => '19034397','latlng' => '13,-2','demonym' => 'Burkinabe','timezones' => 'UTC','currency_name' => 'West African CFA franc','currency_code' => 'XOF','currency_symbol' => 'Fr','flag' => 'https://restcountries.eu/data/bfa.svg','address_format' => ''),
  array('ID' => '40','name' => 'Burundi','slug' => 'burundi','alpha2Code' => 'BI','alpha3Code' => 'BDI','callingCodes' => '257','capital' => 'Bujumbura','region' => 'Africa','subregion' => 'Eastern Africa','population' => '10114505','latlng' => '-3.5,30','demonym' => 'Burundian','timezones' => 'UTC+02:00','currency_name' => 'Burundian franc','currency_code' => 'BIF','currency_symbol' => 'Fr','flag' => 'https://restcountries.eu/data/bdi.svg','address_format' => ''),
  array('ID' => '41','name' => 'Cambodia','slug' => 'cambodia','alpha2Code' => 'KH','alpha3Code' => 'KHM','callingCodes' => '855','capital' => 'Phnom Penh','region' => 'Asia','subregion' => 'South-Eastern Asia','population' => '15626444','latlng' => '13,105','demonym' => 'Cambodian','timezones' => 'UTC+07:00','currency_name' => 'Cambodian riel','currency_code' => 'KHR','currency_symbol' => '?','flag' => 'https://restcountries.eu/data/khm.svg','address_format' => ''),
  array('ID' => '42','name' => 'Cameroon','slug' => 'cameroon','alpha2Code' => 'CM','alpha3Code' => 'CMR','callingCodes' => '237','capital' => 'Yaound�','region' => 'Africa','subregion' => 'Middle Africa','population' => '22709892','latlng' => '6,12','demonym' => 'Cameroonian','timezones' => 'UTC+01:00','currency_name' => 'Central African CFA franc','currency_code' => 'XAF','currency_symbol' => 'Fr','flag' => 'https://restcountries.eu/data/cmr.svg','address_format' => ''),
  array('ID' => '43','name' => 'Canada','slug' => 'canada','alpha2Code' => 'CA','alpha3Code' => 'CAN','callingCodes' => '1','capital' => 'Ottawa','region' => 'Americas','subregion' => 'Northern America','population' => '36155487','latlng' => '60,-95','demonym' => 'Canadian','timezones' => 'UTC-08:00,UTC-07:00,UTC-06:00,UTC-05:00,UTC-04:00,UTC-03:30','currency_name' => 'Canadian dollar','currency_code' => 'CAD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/can.svg','address_format' => ''),
  array('ID' => '44','name' => 'Cabo Verde','slug' => 'cabo-verde','alpha2Code' => 'CV','alpha3Code' => 'CPV','callingCodes' => '238','capital' => 'Praia','region' => 'Africa','subregion' => 'Western Africa','population' => '531239','latlng' => '16,-24','demonym' => 'Cape Verdian','timezones' => 'UTC-01:00','currency_name' => 'Cape Verdean escudo','currency_code' => 'CVE','currency_symbol' => 'Esc','flag' => 'https://restcountries.eu/data/cpv.svg','address_format' => ''),
  array('ID' => '45','name' => 'Cayman Islands','slug' => 'cayman-islands','alpha2Code' => 'KY','alpha3Code' => 'CYM','callingCodes' => '1345','capital' => 'George Town','region' => 'Americas','subregion' => 'Caribbean','population' => '58238','latlng' => '19.5,-80.5','demonym' => 'Caymanian','timezones' => 'UTC-05:00','currency_name' => 'Cayman Islands dollar','currency_code' => 'KYD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/cym.svg','address_format' => ''),
  array('ID' => '46','name' => 'Central African Republic','slug' => 'central-african-republic','alpha2Code' => 'CF','alpha3Code' => 'CAF','callingCodes' => '236','capital' => 'Bangui','region' => 'Africa','subregion' => 'Middle Africa','population' => '4998000','latlng' => '7,21','demonym' => 'Central African','timezones' => 'UTC+01:00','currency_name' => 'Central African CFA franc','currency_code' => 'XAF','currency_symbol' => 'Fr','flag' => 'https://restcountries.eu/data/caf.svg','address_format' => ''),
  array('ID' => '47','name' => 'Chad','slug' => 'chad','alpha2Code' => 'TD','alpha3Code' => 'TCD','callingCodes' => '235','capital' => 'N\'Djamena','region' => 'Africa','subregion' => 'Middle Africa','population' => '14497000','latlng' => '15,19','demonym' => 'Chadian','timezones' => 'UTC+01:00','currency_name' => 'Central African CFA franc','currency_code' => 'XAF','currency_symbol' => 'Fr','flag' => 'https://restcountries.eu/data/tcd.svg','address_format' => ''),
  array('ID' => '48','name' => 'Chile','slug' => 'chile','alpha2Code' => 'CL','alpha3Code' => 'CHL','callingCodes' => '56','capital' => 'Santiago','region' => 'Americas','subregion' => 'South America','population' => '18191900','latlng' => '-30,-71','demonym' => 'Chilean','timezones' => 'UTC-06:00,UTC-04:00','currency_name' => 'Chilean peso','currency_code' => 'CLP','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/chl.svg','address_format' => ''),
  array('ID' => '49','name' => 'China','slug' => 'china','alpha2Code' => 'CN','alpha3Code' => 'CHN','callingCodes' => '86','capital' => 'Beijing','region' => 'Asia','subregion' => 'Eastern Asia','population' => '1377422166','latlng' => '35,105','demonym' => 'Chinese','timezones' => 'UTC+08:00','currency_name' => 'Chinese yuan','currency_code' => 'CNY','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/chn.svg','address_format' => ''),
  array('ID' => '50','name' => 'Christmas Island','slug' => 'christmas-island','alpha2Code' => 'CX','alpha3Code' => 'CXR','callingCodes' => '61','capital' => 'Flying Fish Cove','region' => 'Oceania','subregion' => 'Australia and New Zealand','population' => '2072','latlng' => '-10.5,105.66666666','demonym' => 'Christmas Island','timezones' => 'UTC+07:00','currency_name' => 'Australian dollar','currency_code' => 'AUD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/cxr.svg','address_format' => ''),
  array('ID' => '51','name' => 'Cocos (Keeling) Islands','slug' => 'cocos-keeling-islands','alpha2Code' => 'CC','alpha3Code' => 'CCK','callingCodes' => '61','capital' => 'West Island','region' => 'Oceania','subregion' => 'Australia and New Zealand','population' => '550','latlng' => '-12.5,96.83333333','demonym' => 'Cocos Islander','timezones' => 'UTC+06:30','currency_name' => 'Australian dollar','currency_code' => 'AUD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/cck.svg','address_format' => ''),
  array('ID' => '52','name' => 'Colombia','slug' => 'colombia','alpha2Code' => 'CO','alpha3Code' => 'COL','callingCodes' => '57','capital' => 'Bogot�','region' => 'Americas','subregion' => 'South America','population' => '48759958','latlng' => '4,-72','demonym' => 'Colombian','timezones' => 'UTC-05:00','currency_name' => 'Colombian peso','currency_code' => 'COP','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/col.svg','address_format' => ''),
  array('ID' => '53','name' => 'Comoros','slug' => 'comoros','alpha2Code' => 'KM','alpha3Code' => 'COM','callingCodes' => '269','capital' => 'Moroni','region' => 'Africa','subregion' => 'Eastern Africa','population' => '806153','latlng' => '-12.16666666,44.25','demonym' => 'Comoran','timezones' => 'UTC+03:00','currency_name' => 'Comorian franc','currency_code' => 'KMF','currency_symbol' => 'Fr','flag' => 'https://restcountries.eu/data/com.svg','address_format' => ''),
  array('ID' => '54','name' => 'Congo','slug' => 'congo','alpha2Code' => 'CG','alpha3Code' => 'COG','callingCodes' => '242','capital' => 'Brazzaville','region' => 'Africa','subregion' => 'Middle Africa','population' => '4741000','latlng' => '-1,15','demonym' => 'Congolese','timezones' => 'UTC+01:00','currency_name' => 'Central African CFA franc','currency_code' => 'XAF','currency_symbol' => 'Fr','flag' => 'https://restcountries.eu/data/cog.svg','address_format' => ''),
  array('ID' => '55','name' => 'Democratic Republic of the Congo','slug' => 'democratic-republic-of-the-congo','alpha2Code' => 'CD','alpha3Code' => 'COD','callingCodes' => '243','capital' => 'Kinshasa','region' => 'Africa','subregion' => 'Middle Africa','population' => '85026000','latlng' => '0,25','demonym' => 'Congolese','timezones' => 'UTC+01:00,UTC+02:00','currency_name' => 'Congolese franc','currency_code' => 'CDF','currency_symbol' => 'Fr','flag' => 'https://restcountries.eu/data/cod.svg','address_format' => ''),
  array('ID' => '56','name' => 'Cook Islands','slug' => 'cook-islands','alpha2Code' => 'CK','alpha3Code' => 'COK','callingCodes' => '682','capital' => 'Avarua','region' => 'Oceania','subregion' => 'Polynesia','population' => '18100','latlng' => '-21.23333333,-159.76666666','demonym' => 'Cook Islander','timezones' => 'UTC-10:00','currency_name' => 'New Zealand dollar','currency_code' => 'NZD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/cok.svg','address_format' => ''),
  array('ID' => '57','name' => 'Costa Rica','slug' => 'costa-rica','alpha2Code' => 'CR','alpha3Code' => 'CRI','callingCodes' => '506','capital' => 'San Jos�','region' => 'Americas','subregion' => 'Central America','population' => '4890379','latlng' => '10,-84','demonym' => 'Costa Rican','timezones' => 'UTC-06:00','currency_name' => 'Costa Rican col�n','currency_code' => 'CRC','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/cri.svg','address_format' => ''),
  array('ID' => '58','name' => 'Croatia','slug' => 'croatia','alpha2Code' => 'HR','alpha3Code' => 'HRV','callingCodes' => '385','capital' => 'Zagreb','region' => 'Europe','subregion' => 'Southern Europe','population' => '4190669','latlng' => '45.16666666,15.5','demonym' => 'Croatian','timezones' => 'UTC+01:00','currency_name' => 'Croatian kuna','currency_code' => 'HRK','currency_symbol' => 'kn','flag' => 'https://restcountries.eu/data/hrv.svg','address_format' => ''),
  array('ID' => '59','name' => 'Cuba','slug' => 'cuba','alpha2Code' => 'CU','alpha3Code' => 'CUB','callingCodes' => '53','capital' => 'Havana','region' => 'Americas','subregion' => 'Caribbean','population' => '11239004','latlng' => '21.5,-80','demonym' => 'Cuban','timezones' => 'UTC-05:00','currency_name' => 'Cuban convertible peso','currency_code' => 'CUC','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/cub.svg','address_format' => ''),
  array('ID' => '60','name' => 'Cura�ao','slug' => 'curacao','alpha2Code' => 'CW','alpha3Code' => 'CUW','callingCodes' => '599','capital' => 'Willemstad','region' => 'Americas','subregion' => 'Caribbean','population' => '154843','latlng' => '12.116667,-68.933333','demonym' => 'Dutch','timezones' => 'UTC-04:00','currency_name' => 'Netherlands Antillean guilder','currency_code' => 'ANG','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/cuw.svg','address_format' => ''),
  array('ID' => '61','name' => 'Cyprus','slug' => 'cyprus','alpha2Code' => 'CY','alpha3Code' => 'CYP','callingCodes' => '357','capital' => 'Nicosia','region' => 'Europe','subregion' => 'Southern Europe','population' => '847000','latlng' => '35,33','demonym' => 'Cypriot','timezones' => 'UTC+02:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/cyp.svg','address_format' => ''),
  array('ID' => '62','name' => 'Czechia','slug' => 'czechia','alpha2Code' => 'CZ','alpha3Code' => 'CZE','callingCodes' => '420','capital' => 'Prague','region' => 'Europe','subregion' => 'Eastern Europe','population' => '10558524','latlng' => '49.75,15.5','demonym' => 'Czech','timezones' => 'UTC+01:00','currency_name' => 'Czech koruna','currency_code' => 'CZK','currency_symbol' => 'Kc','flag' => 'https://restcountries.eu/data/cze.svg','address_format' => ''),
  array('ID' => '63','name' => 'Denmark','slug' => 'denmark','alpha2Code' => 'DK','alpha3Code' => 'DNK','callingCodes' => '45','capital' => 'Copenhagen','region' => 'Europe','subregion' => 'Northern Europe','population' => '5717014','latlng' => '56,10','demonym' => 'Danish','timezones' => 'UTC-04:00,UTC-03:00,UTC-01:00,UTC,UTC+01:00','currency_name' => 'Danish krone','currency_code' => 'DKK','currency_symbol' => 'kr','flag' => 'https://restcountries.eu/data/dnk.svg','address_format' => ''),
  array('ID' => '64','name' => 'Djibouti','slug' => 'djibouti','alpha2Code' => 'DJ','alpha3Code' => 'DJI','callingCodes' => '253','capital' => 'Djibouti','region' => 'Africa','subregion' => 'Eastern Africa','population' => '900000','latlng' => '11.5,43','demonym' => 'Djibouti','timezones' => 'UTC+03:00','currency_name' => 'Djiboutian franc','currency_code' => 'DJF','currency_symbol' => 'Fr','flag' => 'https://restcountries.eu/data/dji.svg','address_format' => ''),
  array('ID' => '65','name' => 'Dominica','slug' => 'dominica','alpha2Code' => 'DM','alpha3Code' => 'DMA','callingCodes' => '1767','capital' => 'Roseau','region' => 'Americas','subregion' => 'Caribbean','population' => '71293','latlng' => '15.41666666,-61.33333333','demonym' => 'Dominican','timezones' => 'UTC-04:00','currency_name' => 'East Caribbean dollar','currency_code' => 'XCD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/dma.svg','address_format' => ''),
  array('ID' => '66','name' => 'Dominican Republic','slug' => 'dominican-republic','alpha2Code' => 'DO','alpha3Code' => 'DOM','callingCodes' => '1809,1829,1849','capital' => 'Santo Domingo','region' => 'Americas','subregion' => 'Caribbean','population' => '10075045','latlng' => '19,-70.66666666','demonym' => 'Dominican','timezones' => 'UTC-04:00','currency_name' => 'Dominican peso','currency_code' => 'DOP','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/dom.svg','address_format' => ''),
  array('ID' => '67','name' => 'Ecuador','slug' => 'ecuador','alpha2Code' => 'EC','alpha3Code' => 'ECU','callingCodes' => '593','capital' => 'Quito','region' => 'Americas','subregion' => 'South America','population' => '16545799','latlng' => '-2,-77.5','demonym' => 'Ecuadorean','timezones' => 'UTC-06:00,UTC-05:00','currency_name' => 'United States dollar','currency_code' => 'USD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/ecu.svg','address_format' => ''),
  array('ID' => '68','name' => 'Egypt','slug' => 'egypt','alpha2Code' => 'EG','alpha3Code' => 'EGY','callingCodes' => '20','capital' => 'Cairo','region' => 'Africa','subregion' => 'Northern Africa','population' => '91290000','latlng' => '27,30','demonym' => 'Egyptian','timezones' => 'UTC+02:00','currency_name' => 'Egyptian pound','currency_code' => 'EGP','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/egy.svg','address_format' => ''),
  array('ID' => '69','name' => 'El Salvador','slug' => 'el-salvador','alpha2Code' => 'SV','alpha3Code' => 'SLV','callingCodes' => '503','capital' => 'San Salvador','region' => 'Americas','subregion' => 'Central America','population' => '6520675','latlng' => '13.83333333,-88.91666666','demonym' => 'Salvadoran','timezones' => 'UTC-06:00','currency_name' => 'United States dollar','currency_code' => 'USD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/slv.svg','address_format' => ''),
  array('ID' => '70','name' => 'Equatorial Guinea','slug' => 'equatorial-guinea','alpha2Code' => 'GQ','alpha3Code' => 'GNQ','callingCodes' => '240','capital' => 'Malabo','region' => 'Africa','subregion' => 'Middle Africa','population' => '1222442','latlng' => '2,10','demonym' => 'Equatorial Guinean','timezones' => 'UTC+01:00','currency_name' => 'Central African CFA franc','currency_code' => 'XAF','currency_symbol' => 'Fr','flag' => 'https://restcountries.eu/data/gnq.svg','address_format' => ''),
  array('ID' => '71','name' => 'Eritrea','slug' => 'eritrea','alpha2Code' => 'ER','alpha3Code' => 'ERI','callingCodes' => '291','capital' => 'Asmara','region' => 'Africa','subregion' => 'Eastern Africa','population' => '5352000','latlng' => '15,39','demonym' => 'Eritrean','timezones' => 'UTC+03:00','currency_name' => 'Eritrean nakfa','currency_code' => 'ERN','currency_symbol' => 'Nfk','flag' => 'https://restcountries.eu/data/eri.svg','address_format' => ''),
  array('ID' => '72','name' => 'Estonia','slug' => 'estonia','alpha2Code' => 'EE','alpha3Code' => 'EST','callingCodes' => '372','capital' => 'Tallinn','region' => 'Europe','subregion' => 'Northern Europe','population' => '1315944','latlng' => '59,26','demonym' => 'Estonian','timezones' => 'UTC+02:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/est.svg','address_format' => ''),
  array('ID' => '73','name' => 'Ethiopia','slug' => 'ethiopia','alpha2Code' => 'ET','alpha3Code' => 'ETH','callingCodes' => '251','capital' => 'Addis Ababa','region' => 'Africa','subregion' => 'Eastern Africa','population' => '92206005','latlng' => '8,38','demonym' => 'Ethiopian','timezones' => 'UTC+03:00','currency_name' => 'Ethiopian birr','currency_code' => 'ETB','currency_symbol' => 'Br','flag' => 'https://restcountries.eu/data/eth.svg','address_format' => ''),
  array('ID' => '74','name' => 'Falkland Islands','slug' => 'falkland-islands','alpha2Code' => 'FK','alpha3Code' => 'FLK','callingCodes' => '500','capital' => 'Stanley','region' => 'Americas','subregion' => 'South America','population' => '2563','latlng' => '-51.75,-59','demonym' => 'Falkland Islander','timezones' => 'UTC-04:00','currency_name' => 'Falkland Islands pound','currency_code' => 'FKP','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/flk.svg','address_format' => ''),
  array('ID' => '75','name' => 'Faroe Islands','slug' => 'faroe-islands','alpha2Code' => 'FO','alpha3Code' => 'FRO','callingCodes' => '298','capital' => 'T�rshavn','region' => 'Europe','subregion' => 'Northern Europe','population' => '49376','latlng' => '62,-7','demonym' => 'Faroese','timezones' => 'UTC+00:00','currency_name' => 'Danish krone','currency_code' => 'DKK','currency_symbol' => 'kr','flag' => 'https://restcountries.eu/data/fro.svg','address_format' => ''),
  array('ID' => '76','name' => 'Fiji','slug' => 'fiji','alpha2Code' => 'FJ','alpha3Code' => 'FJI','callingCodes' => '679','capital' => 'Suva','region' => 'Oceania','subregion' => 'Melanesia','population' => '867000','latlng' => '-18,175','demonym' => 'Fijian','timezones' => 'UTC+12:00','currency_name' => 'Fijian dollar','currency_code' => 'FJD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/fji.svg','address_format' => ''),
  array('ID' => '77','name' => 'Finland','slug' => 'finland','alpha2Code' => 'FI','alpha3Code' => 'FIN','callingCodes' => '358','capital' => 'Helsinki','region' => 'Europe','subregion' => 'Northern Europe','population' => '5491817','latlng' => '64,26','demonym' => 'Finnish','timezones' => 'UTC+02:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/fin.svg','address_format' => ''),
  array('ID' => '78','name' => 'France','slug' => 'france','alpha2Code' => 'FR','alpha3Code' => 'FRA','callingCodes' => '33','capital' => 'Paris','region' => 'Europe','subregion' => 'Western Europe','population' => '66710000','latlng' => '46,2','demonym' => 'French','timezones' => 'UTC-10:00,UTC-09:30,UTC-09:00,UTC-08:00,UTC-04:00,UTC-03:00,UTC+01:00,UTC+03:00,UTC+04:00,UTC+05:00,UTC+11:00,UTC+12:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/fra.svg','address_format' => ''),
  array('ID' => '79','name' => 'French Guiana','slug' => 'french-guiana','alpha2Code' => 'GF','alpha3Code' => 'GUF','callingCodes' => '594','capital' => 'Cayenne','region' => 'Americas','subregion' => 'South America','population' => '254541','latlng' => '4,-53','demonym' => '','timezones' => 'UTC-03:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/guf.svg','address_format' => ''),
  array('ID' => '80','name' => 'French Polynesia','slug' => 'french-polynesia','alpha2Code' => 'PF','alpha3Code' => 'PYF','callingCodes' => '689','capital' => 'Papeete','region' => 'Oceania','subregion' => 'Polynesia','population' => '271800','latlng' => '-15,-140','demonym' => 'French Polynesian','timezones' => 'UTC-10:00,UTC-09:30,UTC-09:00','currency_name' => 'CFP franc','currency_code' => 'XPF','currency_symbol' => 'Fr','flag' => 'https://restcountries.eu/data/pyf.svg','address_format' => ''),
  array('ID' => '81','name' => 'French Southern Territories','slug' => 'french-southern-territories','alpha2Code' => 'TF','alpha3Code' => 'ATF','callingCodes' => '','capital' => 'Port-aux-Fran�ais','region' => 'Africa','subregion' => 'Southern Africa','population' => '140','latlng' => '-49.25,69.167','demonym' => 'French','timezones' => 'UTC+05:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/atf.svg','address_format' => ''),
  array('ID' => '82','name' => 'Gabon','slug' => 'gabon','alpha2Code' => 'GA','alpha3Code' => 'GAB','callingCodes' => '241','capital' => 'Libreville','region' => 'Africa','subregion' => 'Middle Africa','population' => '1802278','latlng' => '-1,11.75','demonym' => 'Gabonese','timezones' => 'UTC+01:00','currency_name' => 'Central African CFA franc','currency_code' => 'XAF','currency_symbol' => 'Fr','flag' => 'https://restcountries.eu/data/gab.svg','address_format' => ''),
  array('ID' => '83','name' => 'Gambia','slug' => 'gambia','alpha2Code' => 'GM','alpha3Code' => 'GMB','callingCodes' => '220','capital' => 'Banjul','region' => 'Africa','subregion' => 'Western Africa','population' => '1882450','latlng' => '13.46666666,-16.56666666','demonym' => 'Gambian','timezones' => 'UTC+00:00','currency_name' => 'Gambian dalasi','currency_code' => 'GMD','currency_symbol' => 'D','flag' => 'https://restcountries.eu/data/gmb.svg','address_format' => ''),
  array('ID' => '84','name' => 'Georgia','slug' => 'georgia','alpha2Code' => 'GE','alpha3Code' => 'GEO','callingCodes' => '995','capital' => 'Tbilisi','region' => 'Asia','subregion' => 'Western Asia','population' => '3720400','latlng' => '42,43.5','demonym' => 'Georgian','timezones' => 'UTC-05:00','currency_name' => 'Georgian Lari','currency_code' => 'GEL','currency_symbol' => '?','flag' => 'https://restcountries.eu/data/geo.svg','address_format' => ''),
  array('ID' => '85','name' => 'Germany','slug' => 'germany','alpha2Code' => 'DE','alpha3Code' => 'DEU','callingCodes' => '49','capital' => 'Berlin','region' => 'Europe','subregion' => 'Western Europe','population' => '81770900','latlng' => '51,9','demonym' => 'German','timezones' => 'UTC+01:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/deu.svg','address_format' => ''),
  array('ID' => '86','name' => 'Ghana','slug' => 'ghana','alpha2Code' => 'GH','alpha3Code' => 'GHA','callingCodes' => '233','capital' => 'Accra','region' => 'Africa','subregion' => 'Western Africa','population' => '27670174','latlng' => '8,-2','demonym' => 'Ghanaian','timezones' => 'UTC','currency_name' => 'Ghanaian cedi','currency_code' => 'GHS','currency_symbol' => '?','flag' => 'https://restcountries.eu/data/gha.svg','address_format' => ''),
  array('ID' => '87','name' => 'Gibraltar','slug' => 'gibraltar','alpha2Code' => 'GI','alpha3Code' => 'GIB','callingCodes' => '350','capital' => 'Gibraltar','region' => 'Europe','subregion' => 'Southern Europe','population' => '33140','latlng' => '36.13333333,-5.35','demonym' => 'Gibraltar','timezones' => 'UTC+01:00','currency_name' => 'Gibraltar pound','currency_code' => 'GIP','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/gib.svg','address_format' => ''),
  array('ID' => '88','name' => 'Greece','slug' => 'greece','alpha2Code' => 'GR','alpha3Code' => 'GRC','callingCodes' => '30','capital' => 'Athens','region' => 'Europe','subregion' => 'Southern Europe','population' => '10858018','latlng' => '39,22','demonym' => 'Greek','timezones' => 'UTC+02:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/grc.svg','address_format' => ''),
  array('ID' => '89','name' => 'Greenland','slug' => 'greenland','alpha2Code' => 'GL','alpha3Code' => 'GRL','callingCodes' => '299','capital' => 'Nuuk','region' => 'Americas','subregion' => 'Northern America','population' => '55847','latlng' => '72,-40','demonym' => 'Greenlandic','timezones' => 'UTC-04:00,UTC-03:00,UTC-01:00,UTC+00:00','currency_name' => 'Danish krone','currency_code' => 'DKK','currency_symbol' => 'kr','flag' => 'https://restcountries.eu/data/grl.svg','address_format' => ''),
  array('ID' => '90','name' => 'Grenada','slug' => 'grenada','alpha2Code' => 'GD','alpha3Code' => 'GRD','callingCodes' => '1473','capital' => 'St. George\'s','region' => 'Americas','subregion' => 'Caribbean','population' => '103328','latlng' => '12.11666666,-61.66666666','demonym' => 'Grenadian','timezones' => 'UTC-04:00','currency_name' => 'East Caribbean dollar','currency_code' => 'XCD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/grd.svg','address_format' => ''),
  array('ID' => '91','name' => 'Guadeloupe','slug' => 'guadeloupe','alpha2Code' => 'GP','alpha3Code' => 'GLP','callingCodes' => '590','capital' => 'Basse-Terre','region' => 'Americas','subregion' => 'Caribbean','population' => '400132','latlng' => '16.25,-61.583333','demonym' => 'Guadeloupian','timezones' => 'UTC-04:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/glp.svg','address_format' => ''),
  array('ID' => '92','name' => 'Guam','slug' => 'guam','alpha2Code' => 'GU','alpha3Code' => 'GUM','callingCodes' => '1671','capital' => 'Hag�t�a','region' => 'Oceania','subregion' => 'Micronesia','population' => '184200','latlng' => '13.46666666,144.78333333','demonym' => 'Guamanian','timezones' => 'UTC+10:00','currency_name' => 'United States dollar','currency_code' => 'USD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/gum.svg','address_format' => ''),
  array('ID' => '93','name' => 'Guatemala','slug' => 'guatemala','alpha2Code' => 'GT','alpha3Code' => 'GTM','callingCodes' => '502','capital' => 'Guatemala City','region' => 'Americas','subregion' => 'Central America','population' => '16176133','latlng' => '15.5,-90.25','demonym' => 'Guatemalan','timezones' => 'UTC-06:00','currency_name' => 'Guatemalan quetzal','currency_code' => 'GTQ','currency_symbol' => 'Q','flag' => 'https://restcountries.eu/data/gtm.svg','address_format' => ''),
  array('ID' => '94','name' => 'Guernsey','slug' => 'guernsey','alpha2Code' => 'GG','alpha3Code' => 'GGY','callingCodes' => '44','capital' => 'St. Peter Port','region' => 'Europe','subregion' => 'Northern Europe','population' => '62999','latlng' => '49.46666666,-2.58333333','demonym' => 'Channel Islander','timezones' => 'UTC+00:00','currency_name' => 'British pound','currency_code' => 'GBP','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/ggy.svg','address_format' => ''),
  array('ID' => '95','name' => 'Guinea','slug' => 'guinea','alpha2Code' => 'GN','alpha3Code' => 'GIN','callingCodes' => '224','capital' => 'Conakry','region' => 'Africa','subregion' => 'Western Africa','population' => '12947000','latlng' => '11,-10','demonym' => 'Guinean','timezones' => 'UTC','currency_name' => 'Guinean franc','currency_code' => 'GNF','currency_symbol' => 'Fr','flag' => 'https://restcountries.eu/data/gin.svg','address_format' => ''),
  array('ID' => '96','name' => 'Guinea-Bissau','slug' => 'guinea-bissau','alpha2Code' => 'GW','alpha3Code' => 'GNB','callingCodes' => '245','capital' => 'Bissau','region' => 'Africa','subregion' => 'Western Africa','population' => '1547777','latlng' => '12,-15','demonym' => 'Guinea-Bissauan','timezones' => 'UTC','currency_name' => 'West African CFA franc','currency_code' => 'XOF','currency_symbol' => 'Fr','flag' => 'https://restcountries.eu/data/gnb.svg','address_format' => ''),
  array('ID' => '97','name' => 'Guyana','slug' => 'guyana','alpha2Code' => 'GY','alpha3Code' => 'GUY','callingCodes' => '592','capital' => 'Georgetown','region' => 'Americas','subregion' => 'South America','population' => '746900','latlng' => '5,-59','demonym' => 'Guyanese','timezones' => 'UTC-04:00','currency_name' => 'Guyanese dollar','currency_code' => 'GYD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/guy.svg','address_format' => ''),
  array('ID' => '98','name' => 'Haiti','slug' => 'haiti','alpha2Code' => 'HT','alpha3Code' => 'HTI','callingCodes' => '509','capital' => 'Port-au-Prince','region' => 'Americas','subregion' => 'Caribbean','population' => '11078033','latlng' => '19,-72.41666666','demonym' => 'Haitian','timezones' => 'UTC-05:00','currency_name' => 'Haitian gourde','currency_code' => 'HTG','currency_symbol' => 'G','flag' => 'https://restcountries.eu/data/hti.svg','address_format' => ''),
  array('ID' => '99','name' => 'Heard Island and McDonald Islands','slug' => 'heard-island-and-mcdonald-islands','alpha2Code' => 'HM','alpha3Code' => 'HMD','callingCodes' => '','capital' => '','region' => '','subregion' => '','population' => '0','latlng' => '-53.1,72.51666666','demonym' => 'Heard and McDonald Islander','timezones' => 'UTC+05:00','currency_name' => 'Australian dollar','currency_code' => 'AUD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/hmd.svg','address_format' => ''),
  array('ID' => '100','name' => 'Holy See','slug' => 'holy-see','alpha2Code' => 'VA','alpha3Code' => 'VAT','callingCodes' => '379','capital' => 'Rome','region' => 'Europe','subregion' => 'Southern Europe','population' => '451','latlng' => '41.9,12.45','demonym' => '','timezones' => 'UTC+01:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/vat.svg','address_format' => ''),
  array('ID' => '101','name' => 'Honduras','slug' => 'honduras','alpha2Code' => 'HN','alpha3Code' => 'HND','callingCodes' => '504','capital' => 'Tegucigalpa','region' => 'Americas','subregion' => 'Central America','population' => '8576532','latlng' => '15,-86.5','demonym' => 'Honduran','timezones' => 'UTC-06:00','currency_name' => 'Honduran lempira','currency_code' => 'HNL','currency_symbol' => 'L','flag' => 'https://restcountries.eu/data/hnd.svg','address_format' => ''),
  array('ID' => '102','name' => 'Hong Kong','slug' => 'hong-kong','alpha2Code' => 'HK','alpha3Code' => 'HKG','callingCodes' => '852','capital' => 'City of Victoria','region' => 'Asia','subregion' => 'Eastern Asia','population' => '7324300','latlng' => '22.25,114.16666666','demonym' => 'Chinese','timezones' => 'UTC+08:00','currency_name' => 'Hong Kong dollar','currency_code' => 'HKD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/hkg.svg','address_format' => ''),
  array('ID' => '103','name' => 'Hungary','slug' => 'hungary','alpha2Code' => 'HU','alpha3Code' => 'HUN','callingCodes' => '36','capital' => 'Budapest','region' => 'Europe','subregion' => 'Eastern Europe','population' => '9823000','latlng' => '47,20','demonym' => 'Hungarian','timezones' => 'UTC+01:00','currency_name' => 'Hungarian forint','currency_code' => 'HUF','currency_symbol' => 'Ft','flag' => 'https://restcountries.eu/data/hun.svg','address_format' => ''),
  array('ID' => '104','name' => 'Iceland','slug' => 'iceland','alpha2Code' => 'IS','alpha3Code' => 'ISL','callingCodes' => '354','capital' => 'Reykjav�k','region' => 'Europe','subregion' => 'Northern Europe','population' => '334300','latlng' => '65,-18','demonym' => 'Icelander','timezones' => 'UTC','currency_name' => 'Icelandic kr�na','currency_code' => 'ISK','currency_symbol' => 'kr','flag' => 'https://restcountries.eu/data/isl.svg','address_format' => ''),
  array('ID' => '105','name' => 'India','slug' => 'india','alpha2Code' => 'IN','alpha3Code' => 'IND','callingCodes' => '91','capital' => 'New Delhi','region' => 'Asia','subregion' => 'Southern Asia','population' => '1295210000','latlng' => '20,77','demonym' => 'Indian','timezones' => 'UTC+05:30','currency_name' => 'Indian rupee','currency_code' => 'INR','currency_symbol' => '?','flag' => 'https://restcountries.eu/data/ind.svg','address_format' => ''),
  array('ID' => '106','name' => 'Indonesia','slug' => 'indonesia','alpha2Code' => 'ID','alpha3Code' => 'IDN','callingCodes' => '62','capital' => 'Jakarta','region' => 'Asia','subregion' => 'South-Eastern Asia','population' => '258705000','latlng' => '-5,120','demonym' => 'Indonesian','timezones' => 'UTC+07:00,UTC+08:00,UTC+09:00','currency_name' => 'Indonesian rupiah','currency_code' => 'IDR','currency_symbol' => 'Rp','flag' => 'https://restcountries.eu/data/idn.svg','address_format' => ''),
  array('ID' => '107','name' => 'C�te d\'Ivoire','slug' => 'cote-divoire','alpha2Code' => 'CI','alpha3Code' => 'CIV','callingCodes' => '225','capital' => 'Yamoussoukro','region' => 'Africa','subregion' => 'Western Africa','population' => '22671331','latlng' => '8,-5','demonym' => 'Ivorian','timezones' => 'UTC','currency_name' => 'West African CFA franc','currency_code' => 'XOF','currency_symbol' => 'Fr','flag' => 'https://restcountries.eu/data/civ.svg','address_format' => ''),
  array('ID' => '108','name' => 'Iran','slug' => 'iran-islamic-republic-of','alpha2Code' => 'IR','alpha3Code' => 'IRN','callingCodes' => '98','capital' => 'Tehran','region' => 'Asia','subregion' => 'Southern Asia','population' => '79369900','latlng' => '32,53','demonym' => 'Iranian','timezones' => 'UTC+03:30','currency_name' => 'Iranian rial','currency_code' => 'IRR','currency_symbol' => '?','flag' => 'https://restcountries.eu/data/irn.svg','address_format' => ''),
  array('ID' => '109','name' => 'Iraq','slug' => 'iraq','alpha2Code' => 'IQ','alpha3Code' => 'IRQ','callingCodes' => '964','capital' => 'Baghdad','region' => 'Asia','subregion' => 'Western Asia','population' => '37883543','latlng' => '33,44','demonym' => 'Iraqi','timezones' => 'UTC+03:00','currency_name' => 'Iraqi dinar','currency_code' => 'IQD','currency_symbol' => '?.?','flag' => 'https://restcountries.eu/data/irq.svg','address_format' => ''),
  array('ID' => '110','name' => 'Ireland','slug' => 'ireland','alpha2Code' => 'IE','alpha3Code' => 'IRL','callingCodes' => '353','capital' => 'Dublin','region' => 'Europe','subregion' => 'Northern Europe','population' => '6378000','latlng' => '53,-8','demonym' => 'Irish','timezones' => 'UTC','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/irl.svg','address_format' => ''),
  array('ID' => '111','name' => 'Isle of Man','slug' => 'isle-of-man','alpha2Code' => 'IM','alpha3Code' => 'IMN','callingCodes' => '44','capital' => 'Douglas','region' => 'Europe','subregion' => 'Northern Europe','population' => '84497','latlng' => '54.25,-4.5','demonym' => 'Manx','timezones' => 'UTC+00:00','currency_name' => 'British pound','currency_code' => 'GBP','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/imn.svg','address_format' => ''),
  array('ID' => '112','name' => 'Israel','slug' => 'israel','alpha2Code' => 'IL','alpha3Code' => 'ISR','callingCodes' => '972','capital' => 'Jerusalem','region' => 'Asia','subregion' => 'Western Asia','population' => '8527400','latlng' => '31.5,34.75','demonym' => 'Israeli','timezones' => 'UTC+02:00','currency_name' => 'Israeli new shekel','currency_code' => 'ILS','currency_symbol' => '?','flag' => 'https://restcountries.eu/data/isr.svg','address_format' => ''),
  array('ID' => '113','name' => 'Italy','slug' => 'italy','alpha2Code' => 'IT','alpha3Code' => 'ITA','callingCodes' => '39','capital' => 'Rome','region' => 'Europe','subregion' => 'Southern Europe','population' => '60665551','latlng' => '42.83333333,12.83333333','demonym' => 'Italian','timezones' => 'UTC+01:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/ita.svg','address_format' => ''),
  array('ID' => '114','name' => 'Jamaica','slug' => 'jamaica','alpha2Code' => 'JM','alpha3Code' => 'JAM','callingCodes' => '1876','capital' => 'Kingston','region' => 'Americas','subregion' => 'Caribbean','population' => '2723246','latlng' => '18.25,-77.5','demonym' => 'Jamaican','timezones' => 'UTC-05:00','currency_name' => 'Jamaican dollar','currency_code' => 'JMD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/jam.svg','address_format' => ''),
  array('ID' => '115','name' => 'Japan','slug' => 'japan','alpha2Code' => 'JP','alpha3Code' => 'JPN','callingCodes' => '81','capital' => 'Tokyo','region' => 'Asia','subregion' => 'Eastern Asia','population' => '126960000','latlng' => '36,138','demonym' => 'Japanese','timezones' => 'UTC+09:00','currency_name' => 'Japanese yen','currency_code' => 'JPY','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/jpn.svg','address_format' => ''),
  array('ID' => '116','name' => 'Jersey','slug' => 'jersey','alpha2Code' => 'JE','alpha3Code' => 'JEY','callingCodes' => '44','capital' => 'Saint Helier','region' => 'Europe','subregion' => 'Northern Europe','population' => '100800','latlng' => '49.25,-2.16666666','demonym' => 'Channel Islander','timezones' => 'UTC+01:00','currency_name' => 'British pound','currency_code' => 'GBP','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/jey.svg','address_format' => ''),
  array('ID' => '117','name' => 'Jordan','slug' => 'jordan','alpha2Code' => 'JO','alpha3Code' => 'JOR','callingCodes' => '962','capital' => 'Amman','region' => 'Asia','subregion' => 'Western Asia','population' => '9531712','latlng' => '31,36','demonym' => 'Jordanian','timezones' => 'UTC+03:00','currency_name' => 'Jordanian dinar','currency_code' => 'JOD','currency_symbol' => '?.?','flag' => 'https://restcountries.eu/data/jor.svg','address_format' => ''),
  array('ID' => '118','name' => 'Kazakhstan','slug' => 'kazakhstan','alpha2Code' => 'KZ','alpha3Code' => 'KAZ','callingCodes' => '76,77','capital' => 'Astana','region' => 'Asia','subregion' => 'Central Asia','population' => '17753200','latlng' => '48,68','demonym' => 'Kazakhstani','timezones' => 'UTC+05:00,UTC+06:00','currency_name' => 'Kazakhstani tenge','currency_code' => 'KZT','currency_symbol' => '','flag' => 'https://restcountries.eu/data/kaz.svg','address_format' => ''),
  array('ID' => '119','name' => 'Kenya','slug' => 'kenya','alpha2Code' => 'KE','alpha3Code' => 'KEN','callingCodes' => '254','capital' => 'Nairobi','region' => 'Africa','subregion' => 'Eastern Africa','population' => '47251000','latlng' => '1,38','demonym' => 'Kenyan','timezones' => 'UTC+03:00','currency_name' => 'Kenyan shilling','currency_code' => 'KES','currency_symbol' => 'Sh','flag' => 'https://restcountries.eu/data/ken.svg','address_format' => ''),
  array('ID' => '120','name' => 'Kiribati','slug' => 'kiribati','alpha2Code' => 'KI','alpha3Code' => 'KIR','callingCodes' => '686','capital' => 'South Tarawa','region' => 'Oceania','subregion' => 'Micronesia','population' => '113400','latlng' => '1.41666666,173','demonym' => 'I-Kiribati','timezones' => 'UTC+12:00,UTC+13:00,UTC+14:00','currency_name' => 'Australian dollar','currency_code' => 'AUD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/kir.svg','address_format' => ''),
  array('ID' => '121','name' => 'Kuwait','slug' => 'kuwait','alpha2Code' => 'KW','alpha3Code' => 'KWT','callingCodes' => '965','capital' => 'Kuwait City','region' => 'Asia','subregion' => 'Western Asia','population' => '4183658','latlng' => '29.5,45.75','demonym' => 'Kuwaiti','timezones' => 'UTC+03:00','currency_name' => 'Kuwaiti dinar','currency_code' => 'KWD','currency_symbol' => '?.?','flag' => 'https://restcountries.eu/data/kwt.svg','address_format' => ''),
  array('ID' => '122','name' => 'Kyrgyzstan','slug' => 'kyrgyzstan','alpha2Code' => 'KG','alpha3Code' => 'KGZ','callingCodes' => '996','capital' => 'Bishkek','region' => 'Asia','subregion' => 'Central Asia','population' => '6047800','latlng' => '41,75','demonym' => 'Kirghiz','timezones' => 'UTC+06:00','currency_name' => 'Kyrgyzstani som','currency_code' => 'KGS','currency_symbol' => '?','flag' => 'https://restcountries.eu/data/kgz.svg','address_format' => ''),
  array('ID' => '123','name' => 'Lao People\'s Democratic Republic','slug' => 'lao-peoples-democratic-republic','alpha2Code' => 'LA','alpha3Code' => 'LAO','callingCodes' => '856','capital' => 'Vientiane','region' => 'Asia','subregion' => 'South-Eastern Asia','population' => '6492400','latlng' => '18,105','demonym' => 'Laotian','timezones' => 'UTC+07:00','currency_name' => 'Lao kip','currency_code' => 'LAK','currency_symbol' => '?','flag' => 'https://restcountries.eu/data/lao.svg','address_format' => ''),
  array('ID' => '124','name' => 'Latvia','slug' => 'latvia','alpha2Code' => 'LV','alpha3Code' => 'LVA','callingCodes' => '371','capital' => 'Riga','region' => 'Europe','subregion' => 'Northern Europe','population' => '1961600','latlng' => '57,25','demonym' => 'Latvian','timezones' => 'UTC+02:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/lva.svg','address_format' => ''),
  array('ID' => '125','name' => 'Lebanon','slug' => 'lebanon','alpha2Code' => 'LB','alpha3Code' => 'LBN','callingCodes' => '961','capital' => 'Beirut','region' => 'Asia','subregion' => 'Western Asia','population' => '5988000','latlng' => '33.83333333,35.83333333','demonym' => 'Lebanese','timezones' => 'UTC+02:00','currency_name' => 'Lebanese pound','currency_code' => 'LBP','currency_symbol' => '?.?','flag' => 'https://restcountries.eu/data/lbn.svg','address_format' => ''),
  array('ID' => '126','name' => 'Lesotho','slug' => 'lesotho','alpha2Code' => 'LS','alpha3Code' => 'LSO','callingCodes' => '266','capital' => 'Maseru','region' => 'Africa','subregion' => 'Southern Africa','population' => '1894194','latlng' => '-29.5,28.5','demonym' => 'Mosotho','timezones' => 'UTC+02:00','currency_name' => 'Lesotho loti','currency_code' => 'LSL','currency_symbol' => 'L','flag' => 'https://restcountries.eu/data/lso.svg','address_format' => ''),
  array('ID' => '127','name' => 'Liberia','slug' => 'liberia','alpha2Code' => 'LR','alpha3Code' => 'LBR','callingCodes' => '231','capital' => 'Monrovia','region' => 'Africa','subregion' => 'Western Africa','population' => '4615000','latlng' => '6.5,-9.5','demonym' => 'Liberian','timezones' => 'UTC','currency_name' => 'Liberian dollar','currency_code' => 'LRD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/lbr.svg','address_format' => ''),
  array('ID' => '128','name' => 'Libya','slug' => 'libya','alpha2Code' => 'LY','alpha3Code' => 'LBY','callingCodes' => '218','capital' => 'Tripoli','region' => 'Africa','subregion' => 'Northern Africa','population' => '6385000','latlng' => '25,17','demonym' => 'Libyan','timezones' => 'UTC+01:00','currency_name' => 'Libyan dinar','currency_code' => 'LYD','currency_symbol' => '?.?','flag' => 'https://restcountries.eu/data/lby.svg','address_format' => ''),
  array('ID' => '129','name' => 'Liechtenstein','slug' => 'liechtenstein','alpha2Code' => 'LI','alpha3Code' => 'LIE','callingCodes' => '423','capital' => 'Vaduz','region' => 'Europe','subregion' => 'Western Europe','population' => '37623','latlng' => '47.26666666,9.53333333','demonym' => 'Liechtensteiner','timezones' => 'UTC+01:00','currency_name' => 'Swiss franc','currency_code' => 'CHF','currency_symbol' => 'Fr','flag' => 'https://restcountries.eu/data/lie.svg','address_format' => ''),
  array('ID' => '130','name' => 'Lithuania','slug' => 'lithuania','alpha2Code' => 'LT','alpha3Code' => 'LTU','callingCodes' => '370','capital' => 'Vilnius','region' => 'Europe','subregion' => 'Northern Europe','population' => '2872294','latlng' => '56,24','demonym' => 'Lithuanian','timezones' => 'UTC+02:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/ltu.svg','address_format' => ''),
  array('ID' => '131','name' => 'Luxembourg','slug' => 'luxembourg','alpha2Code' => 'LU','alpha3Code' => 'LUX','callingCodes' => '352','capital' => 'Luxembourg','region' => 'Europe','subregion' => 'Western Europe','population' => '576200','latlng' => '49.75,6.16666666','demonym' => 'Luxembourger','timezones' => 'UTC+01:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/lux.svg','address_format' => ''),
  array('ID' => '132','name' => 'Macao','slug' => 'macao','alpha2Code' => 'MO','alpha3Code' => 'MAC','callingCodes' => '853','capital' => '','region' => 'Asia','subregion' => 'Eastern Asia','population' => '649100','latlng' => '22.16666666,113.55','demonym' => 'Chinese','timezones' => 'UTC+08:00','currency_name' => 'Macanese pataca','currency_code' => 'MOP','currency_symbol' => 'P','flag' => 'https://restcountries.eu/data/mac.svg','address_format' => ''),
  array('ID' => '133','name' => 'Macedonia (the former Yugoslav Republic of)','slug' => 'macedonia-the-former-yugoslav-republic-of','alpha2Code' => 'MK','alpha3Code' => 'MKD','callingCodes' => '389','capital' => 'Skopje','region' => 'Europe','subregion' => 'Southern Europe','population' => '2058539','latlng' => '41.83333333,22','demonym' => 'Macedonian','timezones' => 'UTC+01:00','currency_name' => 'Macedonian denar','currency_code' => 'MKD','currency_symbol' => '???','flag' => 'https://restcountries.eu/data/mkd.svg','address_format' => ''),
  array('ID' => '134','name' => 'Madagascar','slug' => 'madagascar','alpha2Code' => 'MG','alpha3Code' => 'MDG','callingCodes' => '261','capital' => 'Antananarivo','region' => 'Africa','subregion' => 'Eastern Africa','population' => '22434363','latlng' => '-20,47','demonym' => 'Malagasy','timezones' => 'UTC+03:00','currency_name' => 'Malagasy ariary','currency_code' => 'MGA','currency_symbol' => 'Ar','flag' => 'https://restcountries.eu/data/mdg.svg','address_format' => ''),
  array('ID' => '135','name' => 'Malawi','slug' => 'malawi','alpha2Code' => 'MW','alpha3Code' => 'MWI','callingCodes' => '265','capital' => 'Lilongwe','region' => 'Africa','subregion' => 'Eastern Africa','population' => '16832910','latlng' => '-13.5,34','demonym' => 'Malawian','timezones' => 'UTC+02:00','currency_name' => 'Malawian kwacha','currency_code' => 'MWK','currency_symbol' => 'MK','flag' => 'https://restcountries.eu/data/mwi.svg','address_format' => ''),
  array('ID' => '136','name' => 'Malaysia','slug' => 'malaysia','alpha2Code' => 'MY','alpha3Code' => 'MYS','callingCodes' => '60','capital' => 'Kuala Lumpur','region' => 'Asia','subregion' => 'South-Eastern Asia','population' => '31405416','latlng' => '2.5,112.5','demonym' => 'Malaysian','timezones' => 'UTC+08:00','currency_name' => 'Malaysian ringgit','currency_code' => 'MYR','currency_symbol' => 'RM','flag' => 'https://restcountries.eu/data/mys.svg','address_format' => ''),
  array('ID' => '137','name' => 'Maldives','slug' => 'maldives','alpha2Code' => 'MV','alpha3Code' => 'MDV','callingCodes' => '960','capital' => 'Mal�','region' => 'Asia','subregion' => 'Southern Asia','population' => '344023','latlng' => '3.25,73','demonym' => 'Maldivan','timezones' => 'UTC+05:00','currency_name' => 'Maldivian rufiyaa','currency_code' => 'MVR','currency_symbol' => '.?','flag' => 'https://restcountries.eu/data/mdv.svg','address_format' => ''),
  array('ID' => '138','name' => 'Mali','slug' => 'mali','alpha2Code' => 'ML','alpha3Code' => 'MLI','callingCodes' => '223','capital' => 'Bamako','region' => 'Africa','subregion' => 'Western Africa','population' => '18135000','latlng' => '17,-4','demonym' => 'Malian','timezones' => 'UTC','currency_name' => 'West African CFA franc','currency_code' => 'XOF','currency_symbol' => 'Fr','flag' => 'https://restcountries.eu/data/mli.svg','address_format' => ''),
  array('ID' => '139','name' => 'Malta','slug' => 'malta','alpha2Code' => 'MT','alpha3Code' => 'MLT','callingCodes' => '356','capital' => 'Valletta','region' => 'Europe','subregion' => 'Southern Europe','population' => '425384','latlng' => '35.83333333,14.58333333','demonym' => 'Maltese','timezones' => 'UTC+01:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/mlt.svg','address_format' => ''),
  array('ID' => '140','name' => 'Marshall Islands','slug' => 'marshall-islands','alpha2Code' => 'MH','alpha3Code' => 'MHL','callingCodes' => '692','capital' => 'Majuro','region' => 'Oceania','subregion' => 'Micronesia','population' => '54880','latlng' => '9,168','demonym' => 'Marshallese','timezones' => 'UTC+12:00','currency_name' => 'United States dollar','currency_code' => 'USD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/mhl.svg','address_format' => ''),
  array('ID' => '141','name' => 'Martinique','slug' => 'martinique','alpha2Code' => 'MQ','alpha3Code' => 'MTQ','callingCodes' => '596','capital' => 'Fort-de-France','region' => 'Americas','subregion' => 'Caribbean','population' => '378243','latlng' => '14.666667,-61','demonym' => 'French','timezones' => 'UTC-04:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/mtq.svg','address_format' => ''),
  array('ID' => '142','name' => 'Mauritania','slug' => 'mauritania','alpha2Code' => 'MR','alpha3Code' => 'MRT','callingCodes' => '222','capital' => 'Nouakchott','region' => 'Africa','subregion' => 'Western Africa','population' => '3718678','latlng' => '20,-12','demonym' => 'Mauritanian','timezones' => 'UTC','currency_name' => 'Mauritanian ouguiya','currency_code' => 'MRO','currency_symbol' => 'UM','flag' => 'https://restcountries.eu/data/mrt.svg','address_format' => ''),
  array('ID' => '143','name' => 'Mauritius','slug' => 'mauritius','alpha2Code' => 'MU','alpha3Code' => 'MUS','callingCodes' => '230','capital' => 'Port Louis','region' => 'Africa','subregion' => 'Eastern Africa','population' => '1262879','latlng' => '-20.28333333,57.55','demonym' => 'Mauritian','timezones' => 'UTC+04:00','currency_name' => 'Mauritian rupee','currency_code' => 'MUR','currency_symbol' => '?','flag' => 'https://restcountries.eu/data/mus.svg','address_format' => ''),
  array('ID' => '144','name' => 'Mayotte','slug' => 'mayotte','alpha2Code' => 'YT','alpha3Code' => 'MYT','callingCodes' => '262','capital' => 'Mamoudzou','region' => 'Africa','subregion' => 'Eastern Africa','population' => '226915','latlng' => '-12.83333333,45.16666666','demonym' => 'French','timezones' => 'UTC+03:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/myt.svg','address_format' => ''),
  array('ID' => '145','name' => 'Mexico','slug' => 'mexico','alpha2Code' => 'MX','alpha3Code' => 'MEX','callingCodes' => '52','capital' => 'Mexico City','region' => 'Americas','subregion' => 'Central America','population' => '122273473','latlng' => '23,-102','demonym' => 'Mexican','timezones' => 'UTC-08:00,UTC-07:00,UTC-06:00','currency_name' => 'Mexican peso','currency_code' => 'MXN','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/mex.svg','address_format' => ''),
  array('ID' => '146','name' => 'Micronesia','slug' => 'micronesia','alpha2Code' => 'FM','alpha3Code' => 'FSM','callingCodes' => '691','capital' => 'Palikir','region' => 'Oceania','subregion' => 'Micronesia','population' => '102800','latlng' => '6.91666666,158.25','demonym' => 'Micronesian','timezones' => 'UTC+10:00,UTC+11','currency_name' => '[D]','currency_code' => '','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/fsm.svg','address_format' => ''),
  array('ID' => '147','name' => 'Moldova','slug' => 'moldova','alpha2Code' => 'MD','alpha3Code' => 'MDA','callingCodes' => '373','capital' => 'Chi?inau','region' => 'Europe','subregion' => 'Eastern Europe','population' => '3553100','latlng' => '47,29','demonym' => 'Moldovan','timezones' => 'UTC+02:00','currency_name' => 'Moldovan leu','currency_code' => 'MDL','currency_symbol' => 'L','flag' => 'https://restcountries.eu/data/mda.svg','address_format' => ''),
  array('ID' => '148','name' => 'Monaco','slug' => 'monaco','alpha2Code' => 'MC','alpha3Code' => 'MCO','callingCodes' => '377','capital' => 'Monaco','region' => 'Europe','subregion' => 'Western Europe','population' => '38400','latlng' => '43.73333333,7.4','demonym' => 'Monegasque','timezones' => 'UTC+01:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/mco.svg','address_format' => ''),
  array('ID' => '149','name' => 'Mongolia','slug' => 'mongolia','alpha2Code' => 'MN','alpha3Code' => 'MNG','callingCodes' => '976','capital' => 'Ulan Bator','region' => 'Asia','subregion' => 'Eastern Asia','population' => '3093100','latlng' => '46,105','demonym' => 'Mongolian','timezones' => 'UTC+07:00,UTC+08:00','currency_name' => 'Mongolian t�gr�g','currency_code' => 'MNT','currency_symbol' => '?','flag' => 'https://restcountries.eu/data/mng.svg','address_format' => ''),
  array('ID' => '150','name' => 'Montenegro','slug' => 'montenegro','alpha2Code' => 'ME','alpha3Code' => 'MNE','callingCodes' => '382','capital' => 'Podgorica','region' => 'Europe','subregion' => 'Southern Europe','population' => '621810','latlng' => '42.5,19.3','demonym' => 'Montenegrin','timezones' => 'UTC+01:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/mne.svg','address_format' => ''),
  array('ID' => '151','name' => 'Montserrat','slug' => 'montserrat','alpha2Code' => 'MS','alpha3Code' => 'MSR','callingCodes' => '1664','capital' => 'Plymouth','region' => 'Americas','subregion' => 'Caribbean','population' => '4922','latlng' => '16.75,-62.2','demonym' => 'Montserratian','timezones' => 'UTC-04:00','currency_name' => 'East Caribbean dollar','currency_code' => 'XCD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/msr.svg','address_format' => ''),
  array('ID' => '152','name' => 'Morocco','slug' => 'morocco','alpha2Code' => 'MA','alpha3Code' => 'MAR','callingCodes' => '212','capital' => 'Rabat','region' => 'Africa','subregion' => 'Northern Africa','population' => '33337529','latlng' => '32,-5','demonym' => 'Moroccan','timezones' => 'UTC','currency_name' => 'Moroccan dirham','currency_code' => 'MAD','currency_symbol' => '?.?','flag' => 'https://restcountries.eu/data/mar.svg','address_format' => ''),
  array('ID' => '153','name' => 'Mozambique','slug' => 'mozambique','alpha2Code' => 'MZ','alpha3Code' => 'MOZ','callingCodes' => '258','capital' => 'Maputo','region' => 'Africa','subregion' => 'Eastern Africa','population' => '26423700','latlng' => '-18.25,35','demonym' => 'Mozambican','timezones' => 'UTC+02:00','currency_name' => 'Mozambican metical','currency_code' => 'MZN','currency_symbol' => 'MT','flag' => 'https://restcountries.eu/data/moz.svg','address_format' => ''),
  array('ID' => '154','name' => 'Myanmar','slug' => 'myanmar','alpha2Code' => 'MM','alpha3Code' => 'MMR','callingCodes' => '95','capital' => 'Naypyidaw','region' => 'Asia','subregion' => 'South-Eastern Asia','population' => '51419420','latlng' => '22,98','demonym' => 'Burmese','timezones' => 'UTC+06:30','currency_name' => 'Burmese kyat','currency_code' => 'MMK','currency_symbol' => 'Ks','flag' => 'https://restcountries.eu/data/mmr.svg','address_format' => ''),
  array('ID' => '155','name' => 'Namibia','slug' => 'namibia','alpha2Code' => 'NA','alpha3Code' => 'NAM','callingCodes' => '264','capital' => 'Windhoek','region' => 'Africa','subregion' => 'Southern Africa','population' => '2324388','latlng' => '-22,17','demonym' => 'Namibian','timezones' => 'UTC+01:00','currency_name' => 'Namibian dollar','currency_code' => 'NAD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/nam.svg','address_format' => ''),
  array('ID' => '156','name' => 'Nauru','slug' => 'nauru','alpha2Code' => 'NR','alpha3Code' => 'NRU','callingCodes' => '674','capital' => 'Yaren','region' => 'Oceania','subregion' => 'Micronesia','population' => '10084','latlng' => '-0.53333333,166.91666666','demonym' => 'Nauruan','timezones' => 'UTC+12:00','currency_name' => 'Australian dollar','currency_code' => 'AUD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/nru.svg','address_format' => ''),
  array('ID' => '157','name' => 'Nepal','slug' => 'nepal','alpha2Code' => 'NP','alpha3Code' => 'NPL','callingCodes' => '977','capital' => 'Kathmandu','region' => 'Asia','subregion' => 'Southern Asia','population' => '28431500','latlng' => '28,84','demonym' => 'Nepalese','timezones' => 'UTC+05:45','currency_name' => 'Nepalese rupee','currency_code' => 'NPR','currency_symbol' => '?','flag' => 'https://restcountries.eu/data/npl.svg','address_format' => ''),
  array('ID' => '158','name' => 'Netherlands','slug' => 'netherlands','alpha2Code' => 'NL','alpha3Code' => 'NLD','callingCodes' => '31','capital' => 'Amsterdam','region' => 'Europe','subregion' => 'Western Europe','population' => '17019800','latlng' => '52.5,5.75','demonym' => 'Dutch','timezones' => 'UTC-04:00,UTC+01:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/nld.svg','address_format' => ''),
  array('ID' => '159','name' => 'New Caledonia','slug' => 'new-caledonia','alpha2Code' => 'NC','alpha3Code' => 'NCL','callingCodes' => '687','capital' => 'Noum�a','region' => 'Oceania','subregion' => 'Melanesia','population' => '268767','latlng' => '-21.5,165.5','demonym' => 'New Caledonian','timezones' => 'UTC+11:00','currency_name' => 'CFP franc','currency_code' => 'XPF','currency_symbol' => 'Fr','flag' => 'https://restcountries.eu/data/ncl.svg','address_format' => ''),
  array('ID' => '160','name' => 'New Zealand','slug' => 'new-zealand','alpha2Code' => 'NZ','alpha3Code' => 'NZL','callingCodes' => '64','capital' => 'Wellington','region' => 'Oceania','subregion' => 'Australia and New Zealand','population' => '4697854','latlng' => '-41,174','demonym' => 'New Zealander','timezones' => 'UTC-11:00,UTC-10:00,UTC+12:00,UTC+12:45,UTC+13:00','currency_name' => 'New Zealand dollar','currency_code' => 'NZD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/nzl.svg','address_format' => ''),
  array('ID' => '161','name' => 'Nicaragua','slug' => 'nicaragua','alpha2Code' => 'NI','alpha3Code' => 'NIC','callingCodes' => '505','capital' => 'Managua','region' => 'Americas','subregion' => 'Central America','population' => '6262703','latlng' => '13,-85','demonym' => 'Nicaraguan','timezones' => 'UTC-06:00','currency_name' => 'Nicaraguan c�rdoba','currency_code' => 'NIO','currency_symbol' => 'C$','flag' => 'https://restcountries.eu/data/nic.svg','address_format' => ''),
  array('ID' => '162','name' => 'Niger','slug' => 'niger','alpha2Code' => 'NE','alpha3Code' => 'NER','callingCodes' => '227','capital' => 'Niamey','region' => 'Africa','subregion' => 'Western Africa','population' => '20715000','latlng' => '16,8','demonym' => 'Nigerien','timezones' => 'UTC+01:00','currency_name' => 'West African CFA franc','currency_code' => 'XOF','currency_symbol' => 'Fr','flag' => 'https://restcountries.eu/data/ner.svg','address_format' => ''),
  array('ID' => '163','name' => 'Nigeria','slug' => 'nigeria','alpha2Code' => 'NG','alpha3Code' => 'NGA','callingCodes' => '234','capital' => 'Abuja','region' => 'Africa','subregion' => 'Western Africa','population' => '186988000','latlng' => '10,8','demonym' => 'Nigerian','timezones' => 'UTC+01:00','currency_name' => 'Nigerian naira','currency_code' => 'NGN','currency_symbol' => '?','flag' => 'https://restcountries.eu/data/nga.svg','address_format' => ''),
  array('ID' => '164','name' => 'Niue','slug' => 'niue','alpha2Code' => 'NU','alpha3Code' => 'NIU','callingCodes' => '683','capital' => 'Alofi','region' => 'Oceania','subregion' => 'Polynesia','population' => '1470','latlng' => '-19.03333333,-169.86666666','demonym' => 'Niuean','timezones' => 'UTC-11:00','currency_name' => 'New Zealand dollar','currency_code' => 'NZD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/niu.svg','address_format' => ''),
  array('ID' => '165','name' => 'Norfolk Island','slug' => 'norfolk-island','alpha2Code' => 'NF','alpha3Code' => 'NFK','callingCodes' => '672','capital' => 'Kingston','region' => 'Oceania','subregion' => 'Australia and New Zealand','population' => '2302','latlng' => '-29.03333333,167.95','demonym' => 'Norfolk Islander','timezones' => 'UTC+11:30','currency_name' => 'Australian dollar','currency_code' => 'AUD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/nfk.svg','address_format' => ''),
  array('ID' => '166','name' => 'North Korea','slug' => 'north-korea','alpha2Code' => 'KP','alpha3Code' => 'PRK','callingCodes' => '850','capital' => 'Pyongyang','region' => 'Asia','subregion' => 'Eastern Asia','population' => '25281000','latlng' => '40,127','demonym' => 'North Korean','timezones' => 'UTC+09:00','currency_name' => 'North Korean won','currency_code' => 'KPW','currency_symbol' => '?','flag' => 'https://restcountries.eu/data/prk.svg','address_format' => ''),
  array('ID' => '167','name' => 'Northern Mariana Islands','slug' => 'northern-mariana-islands','alpha2Code' => 'MP','alpha3Code' => 'MNP','callingCodes' => '1670','capital' => 'Saipan','region' => 'Oceania','subregion' => 'Micronesia','population' => '56940','latlng' => '15.2,145.75','demonym' => 'American','timezones' => 'UTC+10:00','currency_name' => 'United States dollar','currency_code' => 'USD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/mnp.svg','address_format' => ''),
  array('ID' => '168','name' => 'Norway','slug' => 'norway','alpha2Code' => 'NO','alpha3Code' => 'NOR','callingCodes' => '47','capital' => 'Oslo','region' => 'Europe','subregion' => 'Northern Europe','population' => '5223256','latlng' => '62,10','demonym' => 'Norwegian','timezones' => 'UTC+01:00','currency_name' => 'Norwegian krone','currency_code' => 'NOK','currency_symbol' => 'kr','flag' => 'https://restcountries.eu/data/nor.svg','address_format' => ''),
  array('ID' => '169','name' => 'Oman','slug' => 'oman','alpha2Code' => 'OM','alpha3Code' => 'OMN','callingCodes' => '968','capital' => 'Muscat','region' => 'Asia','subregion' => 'Western Asia','population' => '4420133','latlng' => '21,57','demonym' => 'Omani','timezones' => 'UTC+04:00','currency_name' => 'Omani rial','currency_code' => 'OMR','currency_symbol' => '?.?','flag' => 'https://restcountries.eu/data/omn.svg','address_format' => ''),
  array('ID' => '170','name' => 'Pakistan','slug' => 'pakistan','alpha2Code' => 'PK','alpha3Code' => 'PAK','callingCodes' => '92','capital' => 'Islamabad','region' => 'Asia','subregion' => 'Southern Asia','population' => '194125062','latlng' => '30,70','demonym' => 'Pakistani','timezones' => 'UTC+05:00','currency_name' => 'Pakistani rupee','currency_code' => 'PKR','currency_symbol' => '?','flag' => 'https://restcountries.eu/data/pak.svg','address_format' => ''),
  array('ID' => '171','name' => 'Palau','slug' => 'palau','alpha2Code' => 'PW','alpha3Code' => 'PLW','callingCodes' => '680','capital' => 'Ngerulmud','region' => 'Oceania','subregion' => 'Micronesia','population' => '17950','latlng' => '7.5,134.5','demonym' => 'Palauan','timezones' => 'UTC+09:00','currency_name' => '[E]','currency_code' => '(no','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/plw.svg','address_format' => ''),
  array('ID' => '172','name' => 'Palestine','slug' => 'palestine','alpha2Code' => 'PS','alpha3Code' => 'PSE','callingCodes' => '970','capital' => 'Ramallah','region' => 'Asia','subregion' => 'Western Asia','population' => '4682467','latlng' => '31.9,35.2','demonym' => 'Palestinian','timezones' => 'UTC+02:00','currency_name' => 'Israeli new sheqel','currency_code' => 'ILS','currency_symbol' => '?','flag' => 'https://restcountries.eu/data/pse.svg','address_format' => ''),
  array('ID' => '173','name' => 'Panama','slug' => 'panama','alpha2Code' => 'PA','alpha3Code' => 'PAN','callingCodes' => '507','capital' => 'Panama City','region' => 'Americas','subregion' => 'Central America','population' => '3814672','latlng' => '9,-80','demonym' => 'Panamanian','timezones' => 'UTC-05:00','currency_name' => 'Panamanian balboa','currency_code' => 'PAB','currency_symbol' => 'B/.','flag' => 'https://restcountries.eu/data/pan.svg','address_format' => ''),
  array('ID' => '174','name' => 'Papua New Guinea','slug' => 'papua-new-guinea','alpha2Code' => 'PG','alpha3Code' => 'PNG','callingCodes' => '675','capital' => 'Port Moresby','region' => 'Oceania','subregion' => 'Melanesia','population' => '8083700','latlng' => '-6,147','demonym' => 'Papua New Guinean','timezones' => 'UTC+10:00','currency_name' => 'Papua New Guinean kina','currency_code' => 'PGK','currency_symbol' => 'K','flag' => 'https://restcountries.eu/data/png.svg','address_format' => ''),
  array('ID' => '175','name' => 'Paraguay','slug' => 'paraguay','alpha2Code' => 'PY','alpha3Code' => 'PRY','callingCodes' => '595','capital' => 'Asunci�n','region' => 'Americas','subregion' => 'South America','population' => '6854536','latlng' => '-23,-58','demonym' => 'Paraguayan','timezones' => 'UTC-04:00','currency_name' => 'Paraguayan guaran�','currency_code' => 'PYG','currency_symbol' => '?','flag' => 'https://restcountries.eu/data/pry.svg','address_format' => ''),
  array('ID' => '176','name' => 'Peru','slug' => 'peru','alpha2Code' => 'PE','alpha3Code' => 'PER','callingCodes' => '51','capital' => 'Lima','region' => 'Americas','subregion' => 'South America','population' => '31488700','latlng' => '-10,-76','demonym' => 'Peruvian','timezones' => 'UTC-05:00','currency_name' => 'Peruvian sol','currency_code' => 'PEN','currency_symbol' => 'S/.','flag' => 'https://restcountries.eu/data/per.svg','address_format' => ''),
  array('ID' => '177','name' => 'Philippines','slug' => 'philippines','alpha2Code' => 'PH','alpha3Code' => 'PHL','callingCodes' => '63','capital' => 'Manila','region' => 'Asia','subregion' => 'South-Eastern Asia','population' => '103279800','latlng' => '13,122','demonym' => 'Filipino','timezones' => 'UTC+08:00','currency_name' => 'Philippine peso','currency_code' => 'PHP','currency_symbol' => '?','flag' => 'https://restcountries.eu/data/phl.svg','address_format' => ''),
  array('ID' => '178','name' => 'Pitcairn','slug' => 'pitcairn','alpha2Code' => 'PN','alpha3Code' => 'PCN','callingCodes' => '64','capital' => 'Adamstown','region' => 'Oceania','subregion' => 'Polynesia','population' => '56','latlng' => '-25.06666666,-130.1','demonym' => 'Pitcairn Islander','timezones' => 'UTC-08:00','currency_name' => 'New Zealand dollar','currency_code' => 'NZD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/pcn.svg','address_format' => ''),
  array('ID' => '179','name' => 'Poland','slug' => 'poland','alpha2Code' => 'PL','alpha3Code' => 'POL','callingCodes' => '48','capital' => 'Warsaw','region' => 'Europe','subregion' => 'Eastern Europe','population' => '38437239','latlng' => '52,20','demonym' => 'Polish','timezones' => 'UTC+01:00','currency_name' => 'Polish zloty','currency_code' => 'PLN','currency_symbol' => 'zl','flag' => 'https://restcountries.eu/data/pol.svg','address_format' => ''),
  array('ID' => '180','name' => 'Portugal','slug' => 'portugal','alpha2Code' => 'PT','alpha3Code' => 'PRT','callingCodes' => '351','capital' => 'Lisbon','region' => 'Europe','subregion' => 'Southern Europe','population' => '10374822','latlng' => '39.5,-8','demonym' => 'Portuguese','timezones' => 'UTC-01:00,UTC','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/prt.svg','address_format' => ''),
  array('ID' => '181','name' => 'Puerto Rico','slug' => 'puerto-rico','alpha2Code' => 'PR','alpha3Code' => 'PRI','callingCodes' => '1787,1939','capital' => 'San Juan','region' => 'Americas','subregion' => 'Caribbean','population' => '3474182','latlng' => '18.25,-66.5','demonym' => 'Puerto Rican','timezones' => 'UTC-04:00','currency_name' => 'United States dollar','currency_code' => 'USD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/pri.svg','address_format' => ''),
  array('ID' => '182','name' => 'Qatar','slug' => 'qatar','alpha2Code' => 'QA','alpha3Code' => 'QAT','callingCodes' => '974','capital' => 'Doha','region' => 'Asia','subregion' => 'Western Asia','population' => '2587564','latlng' => '25.5,51.25','demonym' => 'Qatari','timezones' => 'UTC+03:00','currency_name' => 'Qatari riyal','currency_code' => 'QAR','currency_symbol' => '?.?','flag' => 'https://restcountries.eu/data/qat.svg','address_format' => ''),
  array('ID' => '183','name' => 'Kosovo','slug' => 'kosovo','alpha2Code' => 'XK','alpha3Code' => 'KOS','callingCodes' => '383','capital' => 'Pristina','region' => 'Europe','subregion' => 'Eastern Europe','population' => '1733842','latlng' => '42.666667,21.166667','demonym' => 'Kosovar','timezones' => 'UTC+01:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/kos.svg','address_format' => ''),
  array('ID' => '184','name' => 'R�union','slug' => 'reunion','alpha2Code' => 'RE','alpha3Code' => 'REU','callingCodes' => '262','capital' => 'Saint-Denis','region' => 'Africa','subregion' => 'Eastern Africa','population' => '840974','latlng' => '-21.15,55.5','demonym' => 'French','timezones' => 'UTC+04:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/reu.svg','address_format' => ''),
  array('ID' => '185','name' => 'Romania','slug' => 'romania','alpha2Code' => 'RO','alpha3Code' => 'ROU','callingCodes' => '40','capital' => 'Bucharest','region' => 'Europe','subregion' => 'Eastern Europe','population' => '19861408','latlng' => '46,25','demonym' => 'Romanian','timezones' => 'UTC+02:00','currency_name' => 'Romanian leu','currency_code' => 'RON','currency_symbol' => 'lei','flag' => 'https://restcountries.eu/data/rou.svg','address_format' => ''),
  array('ID' => '186','name' => 'Russia','slug' => 'russia','alpha2Code' => 'RU','alpha3Code' => 'RUS','callingCodes' => '7','capital' => 'Moscow','region' => 'Europe','subregion' => 'Eastern Europe','population' => '146599183','latlng' => '60,100','demonym' => 'Russian','timezones' => 'UTC+03:00,UTC+04:00,UTC+06:00,UTC+07:00,UTC+08:00,UTC+09:00,UTC+10:00,UTC+11:00,UTC+12:00','currency_name' => 'Russian ruble','currency_code' => 'RUB','currency_symbol' => '?','flag' => 'https://restcountries.eu/data/rus.svg','address_format' => ''),
  array('ID' => '187','name' => 'Rwanda','slug' => 'rwanda','alpha2Code' => 'RW','alpha3Code' => 'RWA','callingCodes' => '250','capital' => 'Kigali','region' => 'Africa','subregion' => 'Eastern Africa','population' => '11553188','latlng' => '-2,30','demonym' => 'Rwandan','timezones' => 'UTC+02:00','currency_name' => 'Rwandan franc','currency_code' => 'RWF','currency_symbol' => 'Fr','flag' => 'https://restcountries.eu/data/rwa.svg','address_format' => ''),
  array('ID' => '188','name' => 'Saint Barth�lemy','slug' => 'saint-barthelemy','alpha2Code' => 'BL','alpha3Code' => 'BLM','callingCodes' => '590','capital' => 'Gustavia','region' => 'Americas','subregion' => 'Caribbean','population' => '9417','latlng' => '18.5,-63.41666666','demonym' => 'Saint Barth�lemy Islander','timezones' => 'UTC-04:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/blm.svg','address_format' => ''),
  array('ID' => '189','name' => 'Saint Helena, Ascension and Tristan da Cunha','slug' => 'saint-helena-ascension-and-tristan-da-cunha','alpha2Code' => 'SH','alpha3Code' => 'SHN','callingCodes' => '290','capital' => 'Jamestown','region' => 'Africa','subregion' => 'Western Africa','population' => '4255','latlng' => '-15.95,-5.7','demonym' => 'Saint Helenian','timezones' => 'UTC+00:00','currency_name' => 'Saint Helena pound','currency_code' => 'SHP','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/shn.svg','address_format' => ''),
  array('ID' => '190','name' => 'Saint Kitts and Nevis','slug' => 'saint-kitts-and-nevis','alpha2Code' => 'KN','alpha3Code' => 'KNA','callingCodes' => '1869','capital' => 'Basseterre','region' => 'Americas','subregion' => 'Caribbean','population' => '46204','latlng' => '17.33333333,-62.75','demonym' => 'Kittian and Nevisian','timezones' => 'UTC-04:00','currency_name' => 'East Caribbean dollar','currency_code' => 'XCD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/kna.svg','address_format' => ''),
  array('ID' => '191','name' => 'Saint Lucia','slug' => 'saint-lucia','alpha2Code' => 'LC','alpha3Code' => 'LCA','callingCodes' => '1758','capital' => 'Castries','region' => 'Americas','subregion' => 'Caribbean','population' => '186000','latlng' => '13.88333333,-60.96666666','demonym' => 'Saint Lucian','timezones' => 'UTC-04:00','currency_name' => 'East Caribbean dollar','currency_code' => 'XCD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/lca.svg','address_format' => ''),
  array('ID' => '192','name' => 'Saint Martin','slug' => 'saint-martin','alpha2Code' => 'MF','alpha3Code' => 'MAF','callingCodes' => '590','capital' => 'Marigot','region' => 'Americas','subregion' => 'Caribbean','population' => '36979','latlng' => '18.08333333,-63.95','demonym' => 'Saint Martin Islander','timezones' => 'UTC-04:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/maf.svg','address_format' => ''),
  array('ID' => '193','name' => 'Saint Pierre and Miquelon','slug' => 'saint-pierre-and-miquelon','alpha2Code' => 'PM','alpha3Code' => 'SPM','callingCodes' => '508','capital' => 'Saint-Pierre','region' => 'Americas','subregion' => 'Northern America','population' => '6069','latlng' => '46.83333333,-56.33333333','demonym' => 'French','timezones' => 'UTC-03:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/spm.svg','address_format' => ''),
  array('ID' => '194','name' => 'Saint Vincent and the Grenadines','slug' => 'saint-vincent-and-the-grenadines','alpha2Code' => 'VC','alpha3Code' => 'VCT','callingCodes' => '1784','capital' => 'Kingstown','region' => 'Americas','subregion' => 'Caribbean','population' => '109991','latlng' => '13.25,-61.2','demonym' => 'Saint Vincentian','timezones' => 'UTC-04:00','currency_name' => 'East Caribbean dollar','currency_code' => 'XCD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/vct.svg','address_format' => ''),
  array('ID' => '195','name' => 'Samoa','slug' => 'samoa','alpha2Code' => 'WS','alpha3Code' => 'WSM','callingCodes' => '685','capital' => 'Apia','region' => 'Oceania','subregion' => 'Polynesia','population' => '194899','latlng' => '-13.58333333,-172.33333333','demonym' => 'Samoan','timezones' => 'UTC+13:00','currency_name' => 'Samoan tala','currency_code' => 'WST','currency_symbol' => 'T','flag' => 'https://restcountries.eu/data/wsm.svg','address_format' => ''),
  array('ID' => '196','name' => 'San Marino','slug' => 'san-marino','alpha2Code' => 'SM','alpha3Code' => 'SMR','callingCodes' => '378','capital' => 'City of San Marino','region' => 'Europe','subregion' => 'Southern Europe','population' => '33005','latlng' => '43.76666666,12.41666666','demonym' => 'Sammarinese','timezones' => 'UTC+01:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/smr.svg','address_format' => ''),
  array('ID' => '197','name' => 'Sao Tome and Principe','slug' => 'sao-tome-and-principe','alpha2Code' => 'ST','alpha3Code' => 'STP','callingCodes' => '239','capital' => 'S�o Tom�','region' => 'Africa','subregion' => 'Middle Africa','population' => '187356','latlng' => '1,7','demonym' => 'Sao Tomean','timezones' => 'UTC','currency_name' => 'S�o Tom� and Pr�ncipe dobra','currency_code' => 'STD','currency_symbol' => 'Db','flag' => 'https://restcountries.eu/data/stp.svg','address_format' => ''),
  array('ID' => '198','name' => 'Saudi Arabia','slug' => 'saudi-arabia','alpha2Code' => 'SA','alpha3Code' => 'SAU','callingCodes' => '966','capital' => 'Riyadh','region' => 'Asia','subregion' => 'Western Asia','population' => '32248200','latlng' => '25,45','demonym' => 'Saudi Arabian','timezones' => 'UTC+03:00','currency_name' => 'Saudi riyal','currency_code' => 'SAR','currency_symbol' => '?.?','flag' => 'https://restcountries.eu/data/sau.svg','address_format' => ''),
  array('ID' => '199','name' => 'Senegal','slug' => 'senegal','alpha2Code' => 'SN','alpha3Code' => 'SEN','callingCodes' => '221','capital' => 'Dakar','region' => 'Africa','subregion' => 'Western Africa','population' => '14799859','latlng' => '14,-14','demonym' => 'Senegalese','timezones' => 'UTC','currency_name' => 'West African CFA franc','currency_code' => 'XOF','currency_symbol' => 'Fr','flag' => 'https://restcountries.eu/data/sen.svg','address_format' => ''),
  array('ID' => '200','name' => 'Serbia','slug' => 'serbia','alpha2Code' => 'RS','alpha3Code' => 'SRB','callingCodes' => '381','capital' => 'Belgrade','region' => 'Europe','subregion' => 'Southern Europe','population' => '7076372','latlng' => '44,21','demonym' => 'Serbian','timezones' => 'UTC+01:00','currency_name' => 'Serbian dinar','currency_code' => 'RSD','currency_symbol' => '???','flag' => 'https://restcountries.eu/data/srb.svg','address_format' => ''),
  array('ID' => '201','name' => 'Seychelles','slug' => 'seychelles','alpha2Code' => 'SC','alpha3Code' => 'SYC','callingCodes' => '248','capital' => 'Victoria','region' => 'Africa','subregion' => 'Eastern Africa','population' => '91400','latlng' => '-4.58333333,55.66666666','demonym' => 'Seychellois','timezones' => 'UTC+04:00','currency_name' => 'Seychellois rupee','currency_code' => 'SCR','currency_symbol' => '?','flag' => 'https://restcountries.eu/data/syc.svg','address_format' => ''),
  array('ID' => '202','name' => 'Sierra Leone','slug' => 'sierra-leone','alpha2Code' => 'SL','alpha3Code' => 'SLE','callingCodes' => '232','capital' => 'Freetown','region' => 'Africa','subregion' => 'Western Africa','population' => '7075641','latlng' => '8.5,-11.5','demonym' => 'Sierra Leonean','timezones' => 'UTC','currency_name' => 'Sierra Leonean leone','currency_code' => 'SLL','currency_symbol' => 'Le','flag' => 'https://restcountries.eu/data/sle.svg','address_format' => ''),
  array('ID' => '203','name' => 'Singapore','slug' => 'singapore','alpha2Code' => 'SG','alpha3Code' => 'SGP','callingCodes' => '65','capital' => 'Singapore','region' => 'Asia','subregion' => 'South-Eastern Asia','population' => '5535000','latlng' => '1.36666666,103.8','demonym' => 'Singaporean','timezones' => 'UTC+08:00','currency_name' => 'Brunei dollar','currency_code' => 'BND','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/sgp.svg','address_format' => ''),
  array('ID' => '204','name' => 'Sint Maarten','slug' => 'sint-maarten','alpha2Code' => 'SX','alpha3Code' => 'SXM','callingCodes' => '1721','capital' => 'Philipsburg','region' => 'Americas','subregion' => 'Caribbean','population' => '38247','latlng' => '18.033333,-63.05','demonym' => 'Dutch','timezones' => 'UTC-04:00','currency_name' => 'Netherlands Antillean guilder','currency_code' => 'ANG','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/sxm.svg','address_format' => ''),
  array('ID' => '205','name' => 'Slovakia','slug' => 'slovakia','alpha2Code' => 'SK','alpha3Code' => 'SVK','callingCodes' => '421','capital' => 'Bratislava','region' => 'Europe','subregion' => 'Eastern Europe','population' => '5426252','latlng' => '48.66666666,19.5','demonym' => 'Slovak','timezones' => 'UTC+01:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/svk.svg','address_format' => ''),
  array('ID' => '206','name' => 'Slovenia','slug' => 'slovenia','alpha2Code' => 'SI','alpha3Code' => 'SVN','callingCodes' => '386','capital' => 'Ljubljana','region' => 'Europe','subregion' => 'Southern Europe','population' => '2064188','latlng' => '46.11666666,14.81666666','demonym' => 'Slovene','timezones' => 'UTC+01:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/svn.svg','address_format' => ''),
  array('ID' => '207','name' => 'Solomon Islands','slug' => 'solomon-islands','alpha2Code' => 'SB','alpha3Code' => 'SLB','callingCodes' => '677','capital' => 'Honiara','region' => 'Oceania','subregion' => 'Melanesia','population' => '642000','latlng' => '-8,159','demonym' => 'Solomon Islander','timezones' => 'UTC+11:00','currency_name' => 'Solomon Islands dollar','currency_code' => 'SBD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/slb.svg','address_format' => ''),
  array('ID' => '208','name' => 'Somalia','slug' => 'somalia','alpha2Code' => 'SO','alpha3Code' => 'SOM','callingCodes' => '252','capital' => 'Mogadishu','region' => 'Africa','subregion' => 'Eastern Africa','population' => '11079000','latlng' => '10,49','demonym' => 'Somali','timezones' => 'UTC+03:00','currency_name' => 'Somali shilling','currency_code' => 'SOS','currency_symbol' => 'Sh','flag' => 'https://restcountries.eu/data/som.svg','address_format' => ''),
  array('ID' => '209','name' => 'South Africa','slug' => 'south-africa','alpha2Code' => 'ZA','alpha3Code' => 'ZAF','callingCodes' => '27','capital' => 'Pretoria','region' => 'Africa','subregion' => 'Southern Africa','population' => '55653654','latlng' => '-29,24','demonym' => 'South African','timezones' => 'UTC+02:00','currency_name' => 'South African rand','currency_code' => 'ZAR','currency_symbol' => 'R','flag' => 'https://restcountries.eu/data/zaf.svg','address_format' => ''),
  array('ID' => '210','name' => 'South Georgia and the South Sandwich Islands','slug' => 'south-georgia-and-the-south-sandwich-islands','alpha2Code' => 'GS','alpha3Code' => 'SGS','callingCodes' => '500','capital' => 'King Edward Point','region' => 'Americas','subregion' => 'South America','population' => '30','latlng' => '-54.5,-37','demonym' => 'South Georgia and the South Sandwich Islander','timezones' => 'UTC-02:00','currency_name' => 'British pound','currency_code' => 'GBP','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/sgs.svg','address_format' => ''),
  array('ID' => '211','name' => 'South Korea','slug' => 'south-korea','alpha2Code' => 'KR','alpha3Code' => 'KOR','callingCodes' => '82','capital' => 'Seoul','region' => 'Asia','subregion' => 'Eastern Asia','population' => '50801405','latlng' => '37,127.5','demonym' => 'South Korean','timezones' => 'UTC+09:00','currency_name' => 'South Korean won','currency_code' => 'KRW','currency_symbol' => '?','flag' => 'https://restcountries.eu/data/kor.svg','address_format' => ''),
  array('ID' => '212','name' => 'South Sudan','slug' => 'south-sudan','alpha2Code' => 'SS','alpha3Code' => 'SSD','callingCodes' => '211','capital' => 'Juba','region' => 'Africa','subregion' => 'Middle Africa','population' => '12131000','latlng' => '7,30','demonym' => 'South Sudanese','timezones' => 'UTC+03:00','currency_name' => 'South Sudanese pound','currency_code' => 'SSP','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/ssd.svg','address_format' => ''),
  array('ID' => '213','name' => 'Spain','slug' => 'spain','alpha2Code' => 'ES','alpha3Code' => 'ESP','callingCodes' => '34','capital' => 'Madrid','region' => 'Europe','subregion' => 'Southern Europe','population' => '46438422','latlng' => '40,-4','demonym' => 'Spanish','timezones' => 'UTC,UTC+01:00','currency_name' => 'Euro','currency_code' => 'EUR','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/esp.svg','address_format' => ''),
  array('ID' => '214','name' => 'Sri Lanka','slug' => 'sri-lanka','alpha2Code' => 'LK','alpha3Code' => 'LKA','callingCodes' => '94','capital' => 'Colombo','region' => 'Asia','subregion' => 'Southern Asia','population' => '20966000','latlng' => '7,81','demonym' => 'Sri Lankan','timezones' => 'UTC+05:30','currency_name' => 'Sri Lankan rupee','currency_code' => 'LKR','currency_symbol' => 'Rs','flag' => 'https://restcountries.eu/data/lka.svg','address_format' => ''),
  array('ID' => '215','name' => 'Sudan','slug' => 'sudan','alpha2Code' => 'SD','alpha3Code' => 'SDN','callingCodes' => '249','capital' => 'Khartoum','region' => 'Africa','subregion' => 'Northern Africa','population' => '39598700','latlng' => '15,30','demonym' => 'Sudanese','timezones' => 'UTC+03:00','currency_name' => 'Sudanese pound','currency_code' => 'SDG','currency_symbol' => '?.?','flag' => 'https://restcountries.eu/data/sdn.svg','address_format' => ''),
  array('ID' => '216','name' => 'Suriname','slug' => 'suriname','alpha2Code' => 'SR','alpha3Code' => 'SUR','callingCodes' => '597','capital' => 'Paramaribo','region' => 'Americas','subregion' => 'South America','population' => '541638','latlng' => '4,-56','demonym' => 'Surinamer','timezones' => 'UTC-03:00','currency_name' => 'Surinamese dollar','currency_code' => 'SRD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/sur.svg','address_format' => ''),
  array('ID' => '217','name' => 'Svalbard and Jan Mayen','slug' => 'svalbard-and-jan-mayen','alpha2Code' => 'SJ','alpha3Code' => 'SJM','callingCodes' => '4779','capital' => 'Longyearbyen','region' => 'Europe','subregion' => 'Northern Europe','population' => '2562','latlng' => '78,20','demonym' => 'Norwegian','timezones' => 'UTC+01:00','currency_name' => 'Norwegian krone','currency_code' => 'NOK','currency_symbol' => 'kr','flag' => 'https://restcountries.eu/data/sjm.svg','address_format' => ''),
  array('ID' => '218','name' => 'Swaziland','slug' => 'swaziland','alpha2Code' => 'SZ','alpha3Code' => 'SWZ','callingCodes' => '268','capital' => 'Lobamba','region' => 'Africa','subregion' => 'Southern Africa','population' => '1132657','latlng' => '-26.5,31.5','demonym' => 'Swazi','timezones' => 'UTC+02:00','currency_name' => 'Swazi lilangeni','currency_code' => 'SZL','currency_symbol' => 'L','flag' => 'https://restcountries.eu/data/swz.svg','address_format' => ''),
  array('ID' => '219','name' => 'Sweden','slug' => 'sweden','alpha2Code' => 'SE','alpha3Code' => 'SWE','callingCodes' => '46','capital' => 'Stockholm','region' => 'Europe','subregion' => 'Northern Europe','population' => '9894888','latlng' => '62,15','demonym' => 'Swedish','timezones' => 'UTC+01:00','currency_name' => 'Swedish krona','currency_code' => 'SEK','currency_symbol' => 'kr','flag' => 'https://restcountries.eu/data/swe.svg','address_format' => ''),
  array('ID' => '220','name' => 'Switzerland','slug' => 'switzerland','alpha2Code' => 'CH','alpha3Code' => 'CHE','callingCodes' => '41','capital' => 'Bern','region' => 'Europe','subregion' => 'Western Europe','population' => '8341600','latlng' => '47,8','demonym' => 'Swiss','timezones' => 'UTC+01:00','currency_name' => 'Swiss franc','currency_code' => 'CHF','currency_symbol' => 'Fr','flag' => 'https://restcountries.eu/data/che.svg','address_format' => ''),
  array('ID' => '221','name' => 'Syrian Arab Republic','slug' => 'syrian-arab-republic','alpha2Code' => 'SY','alpha3Code' => 'SYR','callingCodes' => '963','capital' => 'Damascus','region' => 'Asia','subregion' => 'Western Asia','population' => '18564000','latlng' => '35,38','demonym' => 'Syrian','timezones' => 'UTC+02:00','currency_name' => 'Syrian pound','currency_code' => 'SYP','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/syr.svg','address_format' => ''),
  array('ID' => '222','name' => 'Taiwan','slug' => 'taiwan','alpha2Code' => 'TW','alpha3Code' => 'TWN','callingCodes' => '886','capital' => 'Taipei','region' => 'Asia','subregion' => 'Eastern Asia','population' => '23503349','latlng' => '23.5,121','demonym' => 'Taiwanese','timezones' => 'UTC+08:00','currency_name' => 'New Taiwan dollar','currency_code' => 'TWD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/twn.svg','address_format' => ''),
  array('ID' => '223','name' => 'Tajikistan','slug' => 'tajikistan','alpha2Code' => 'TJ','alpha3Code' => 'TJK','callingCodes' => '992','capital' => 'Dushanbe','region' => 'Asia','subregion' => 'Central Asia','population' => '8593600','latlng' => '39,71','demonym' => 'Tadzhik','timezones' => 'UTC+05:00','currency_name' => 'Tajikistani somoni','currency_code' => 'TJS','currency_symbol' => '??','flag' => 'https://restcountries.eu/data/tjk.svg','address_format' => ''),
  array('ID' => '224','name' => 'Tanzania','slug' => 'tanzania','alpha2Code' => 'TZ','alpha3Code' => 'TZA','callingCodes' => '255','capital' => 'Dodoma','region' => 'Africa','subregion' => 'Eastern Africa','population' => '55155000','latlng' => '-6,35','demonym' => 'Tanzanian','timezones' => 'UTC+03:00','currency_name' => 'Tanzanian shilling','currency_code' => 'TZS','currency_symbol' => 'Sh','flag' => 'https://restcountries.eu/data/tza.svg','address_format' => ''),
  array('ID' => '225','name' => 'Thailand','slug' => 'thailand','alpha2Code' => 'TH','alpha3Code' => 'THA','callingCodes' => '66','capital' => 'Bangkok','region' => 'Asia','subregion' => 'South-Eastern Asia','population' => '65327652','latlng' => '15,100','demonym' => 'Thai','timezones' => 'UTC+07:00','currency_name' => 'Thai baht','currency_code' => 'THB','currency_symbol' => '?','flag' => 'https://restcountries.eu/data/tha.svg','address_format' => ''),
  array('ID' => '226','name' => 'Timor-Leste','slug' => 'timor-leste','alpha2Code' => 'TL','alpha3Code' => 'TLS','callingCodes' => '670','capital' => 'Dili','region' => 'Asia','subregion' => 'South-Eastern Asia','population' => '1167242','latlng' => '-8.83333333,125.91666666','demonym' => 'East Timorese','timezones' => 'UTC+09:00','currency_name' => 'United States dollar','currency_code' => 'USD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/tls.svg','address_format' => ''),
  array('ID' => '228','name' => 'Tokelau','slug' => 'tokelau','alpha2Code' => 'TK','alpha3Code' => 'TKL','callingCodes' => '690','capital' => 'Fakaofo','region' => 'Oceania','subregion' => 'Polynesia','population' => '1411','latlng' => '-9,-172','demonym' => 'Tokelauan','timezones' => 'UTC+13:00','currency_name' => 'New Zealand dollar','currency_code' => 'NZD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/tkl.svg','address_format' => ''),
  array('ID' => '229','name' => 'Tonga','slug' => 'tonga','alpha2Code' => 'TO','alpha3Code' => 'TON','callingCodes' => '676','capital' => 'Nuku\'alofa','region' => 'Oceania','subregion' => 'Polynesia','population' => '103252','latlng' => '-20,-175','demonym' => 'Tongan','timezones' => 'UTC+13:00','currency_name' => 'Tongan pa?anga','currency_code' => 'TOP','currency_symbol' => 'T$','flag' => 'https://restcountries.eu/data/ton.svg','address_format' => ''),
  array('ID' => '230','name' => 'Trinidad and Tobago','slug' => 'trinidad-and-tobago','alpha2Code' => 'TT','alpha3Code' => 'TTO','callingCodes' => '1868','capital' => 'Port of Spain','region' => 'Americas','subregion' => 'Caribbean','population' => '1349667','latlng' => '11,-61','demonym' => 'Trinidadian','timezones' => 'UTC-04:00','currency_name' => 'Trinidad and Tobago dollar','currency_code' => 'TTD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/tto.svg','address_format' => ''),
  array('ID' => '231','name' => 'Tunisia','slug' => 'tunisia','alpha2Code' => 'TN','alpha3Code' => 'TUN','callingCodes' => '216','capital' => 'Tunis','region' => 'Africa','subregion' => 'Northern Africa','population' => '11154400','latlng' => '34,9','demonym' => 'Tunisian','timezones' => 'UTC+01:00','currency_name' => 'Tunisian dinar','currency_code' => 'TND','currency_symbol' => '?.?','flag' => 'https://restcountries.eu/data/tun.svg','address_format' => ''),
  array('ID' => '232','name' => 'Turkey','slug' => 'turkey','alpha2Code' => 'TR','alpha3Code' => 'TUR','callingCodes' => '90','capital' => 'Ankara','region' => 'Asia','subregion' => 'Western Asia','population' => '78741053','latlng' => '39,35','demonym' => 'Turkish','timezones' => 'UTC+03:00','currency_name' => 'Turkish lira','currency_code' => 'TRY','currency_symbol' => '','flag' => 'https://restcountries.eu/data/tur.svg','address_format' => ''),
  array('ID' => '233','name' => 'Turkmenistan','slug' => 'turkmenistan','alpha2Code' => 'TM','alpha3Code' => 'TKM','callingCodes' => '993','capital' => 'Ashgabat','region' => 'Asia','subregion' => 'Central Asia','population' => '4751120','latlng' => '40,60','demonym' => 'Turkmen','timezones' => 'UTC+05:00','currency_name' => 'Turkmenistan manat','currency_code' => 'TMT','currency_symbol' => 'm','flag' => 'https://restcountries.eu/data/tkm.svg','address_format' => ''),
  array('ID' => '234','name' => 'Turks and Caicos Islands','slug' => 'turks-and-caicos-islands','alpha2Code' => 'TC','alpha3Code' => 'TCA','callingCodes' => '1649','capital' => 'Cockburn Town','region' => 'Americas','subregion' => 'Caribbean','population' => '31458','latlng' => '21.75,-71.58333333','demonym' => 'Turks and Caicos Islander','timezones' => 'UTC-04:00','currency_name' => 'United States dollar','currency_code' => 'USD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/tca.svg','address_format' => ''),
  array('ID' => '235','name' => 'Tuvalu','slug' => 'tuvalu','alpha2Code' => 'TV','alpha3Code' => 'TUV','callingCodes' => '688','capital' => 'Funafuti','region' => 'Oceania','subregion' => 'Polynesia','population' => '10640','latlng' => '-8,178','demonym' => 'Tuvaluan','timezones' => 'UTC+12:00','currency_name' => 'Australian dollar','currency_code' => 'AUD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/tuv.svg','address_format' => ''),
  array('ID' => '236','name' => 'Uganda','slug' => 'uganda','alpha2Code' => 'UG','alpha3Code' => 'UGA','callingCodes' => '256','capital' => 'Kampala','region' => 'Africa','subregion' => 'Eastern Africa','population' => '33860700','latlng' => '1,32','demonym' => 'Ugandan','timezones' => 'UTC+03:00','currency_name' => 'Ugandan shilling','currency_code' => 'UGX','currency_symbol' => 'Sh','flag' => 'https://restcountries.eu/data/uga.svg','address_format' => ''),
  array('ID' => '237','name' => 'Ukraine','slug' => 'ukraine','alpha2Code' => 'UA','alpha3Code' => 'UKR','callingCodes' => '380','capital' => 'Kiev','region' => 'Europe','subregion' => 'Eastern Europe','population' => '42692393','latlng' => '49,32','demonym' => 'Ukrainian','timezones' => 'UTC+02:00','currency_name' => 'Ukrainian hryvnia','currency_code' => 'UAH','currency_symbol' => '?','flag' => 'https://restcountries.eu/data/ukr.svg','address_format' => ''),
  array('ID' => '238','name' => 'United Arab Emirates','slug' => 'united-arab-emirates','alpha2Code' => 'AE','alpha3Code' => 'ARE','callingCodes' => '971','capital' => 'Abu Dhabi','region' => 'Asia','subregion' => 'Western Asia','population' => '9856000','latlng' => '24,54','demonym' => 'Emirati','timezones' => 'UTC+04','currency_name' => 'United Arab Emirates dirham','currency_code' => 'AED','currency_symbol' => '?.?','flag' => 'https://restcountries.eu/data/are.svg','address_format' => ''),
  array('ID' => '239','name' => 'United Kingdom','slug' => 'united-kingdom','alpha2Code' => 'GB','alpha3Code' => 'GBR','callingCodes' => '44','capital' => 'London','region' => 'Europe','subregion' => 'Northern Europe','population' => '65110000','latlng' => '54,-2','demonym' => 'British','timezones' => 'UTC-08:00,UTC-05:00,UTC-04:00,UTC-03:00,UTC-02:00,UTC,UTC+01:00,UTC+02:00,UTC+06:00','currency_name' => 'British pound','currency_code' => 'GBP','currency_symbol' => '�','flag' => 'https://restcountries.eu/data/gbr.svg','address_format' => ''),
  array('ID' => '240','name' => 'United States','slug' => 'united-states','alpha2Code' => 'US','alpha3Code' => 'USA','callingCodes' => '1','capital' => 'Washington, D.C.','region' => 'Americas','subregion' => 'Northern America','population' => '323947000','latlng' => '38,-97','demonym' => 'American','timezones' => 'UTC-12:00,UTC-11:00,UTC-10:00,UTC-09:00,UTC-08:00,UTC-07:00,UTC-06:00,UTC-05:00,UTC-04:00,UTC+10:00,UTC+12:00','currency_name' => 'United States dollar','currency_code' => 'USD','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/usa.svg','address_format' => ''),
  array('ID' => '241','name' => 'Uruguay','slug' => 'uruguay','alpha2Code' => 'UY','alpha3Code' => 'URY','callingCodes' => '598','capital' => 'Montevideo','region' => 'Americas','subregion' => 'South America','population' => '3480222','latlng' => '-33,-56','demonym' => 'Uruguayan','timezones' => 'UTC-03:00','currency_name' => 'Uruguayan peso','currency_code' => 'UYU','currency_symbol' => '$','flag' => 'https://restcountries.eu/data/ury.svg','address_format' => ''),
  array('ID' => '242','name' => 'Uzbekistan','slug' => 'uzbekistan','alpha2Code' => 'UZ','alpha3Code' => 'UZB','callingCodes' => '998','capital' => 'Tashkent','region' => 'Asia','subregion' => 'Central Asia','population' => '31576400','latlng' => '41,64','demonym' => 'Uzbekistani','timezones' => 'UTC+05:00','currency_name' => 'Uzbekistani so\'m','currency_code' => 'UZS','currency_symbol' => '','flag' => 'https://restcountries.eu/data/uzb.svg','address_format' => ''),
  array('ID' => '243','name' => 'Vanuatu','slug' => 'vanuatu','alpha2Code' => 'VU','alpha3Code' => 'VUT','callingCodes' => '678','capital' => 'Port Vila','region' => 'Oceania','subregion' => 'Melanesia','population' => '277500','latlng' => '-16,167','demonym' => 'Ni-Vanuatu','timezones' => 'UTC+11:00','currency_name' => 'Vanuatu vatu','currency_code' => 'VUV','currency_symbol' => 'Vt','flag' => 'https://restcountries.eu/data/vut.svg','address_format' => ''),
  array('ID' => '244','name' => 'Venezuela','slug' => 'venezuela','alpha2Code' => 'VE','alpha3Code' => 'VEN','callingCodes' => '58','capital' => 'Caracas','region' => 'Americas','subregion' => 'South America','population' => '31028700','latlng' => '8,-66','demonym' => 'Venezuelan','timezones' => 'UTC-04:00','currency_name' => 'Venezuelan bol�var','currency_code' => 'VEF','currency_symbol' => 'Bs ','flag' => 'https://restcountries.eu/data/ven.svg','address_format' => ''),
  array('ID' => '245','name' => 'Vietnam','slug' => 'vietnam','alpha2Code' => 'VN','alpha3Code' => 'VNM','callingCodes' => '84','capital' => 'Hanoi','region' => 'Asia','subregion' => 'South-Eastern Asia','population' => '92700000','latlng' => '16.16666666,107.83333333','demonym' => 'Vietnamese','timezones' => 'UTC+07:00','currency_name' => 'Vietnamese d?ng','currency_code' => 'VND','currency_symbol' => '?','flag' => 'https://restcountries.eu/data/vnm.svg','address_format' => ''),
  array('ID' => '246','name' => 'Wallis and Futuna','slug' => 'wallis-and-futuna','alpha2Code' => 'WF','alpha3Code' => 'WLF','callingCodes' => '681','capital' => 'Mata-Utu','region' => 'Oceania','subregion' => 'Polynesia','population' => '11750','latlng' => '-13.3,-176.2','demonym' => 'Wallis and Futuna Islander','timezones' => 'UTC+12:00','currency_name' => 'CFP franc','currency_code' => 'XPF','currency_symbol' => 'Fr','flag' => 'https://restcountries.eu/data/wlf.svg','address_format' => ''),
  array('ID' => '247','name' => 'Western Sahara','slug' => 'western-sahara','alpha2Code' => 'EH','alpha3Code' => 'ESH','callingCodes' => '212','capital' => 'El Aai�n','region' => 'Africa','subregion' => 'Northern Africa','population' => '510713','latlng' => '24.5,-13','demonym' => 'Sahrawi','timezones' => 'UTC+00:00','currency_name' => 'Moroccan dirham','currency_code' => 'MAD','currency_symbol' => '?.?','flag' => 'https://restcountries.eu/data/esh.svg','address_format' => ''),
  array('ID' => '248','name' => 'Yemen','slug' => 'yemen','alpha2Code' => 'YE','alpha3Code' => 'YEM','callingCodes' => '967','capital' => 'Sana\'a','region' => 'Asia','subregion' => 'Western Asia','population' => '27478000','latlng' => '15,48','demonym' => 'Yemeni','timezones' => 'UTC+03:00','currency_name' => 'Yemeni rial','currency_code' => 'YER','currency_symbol' => '?','flag' => 'https://restcountries.eu/data/yem.svg','address_format' => ''),
  array('ID' => '249','name' => 'Zambia','slug' => 'zambia','alpha2Code' => 'ZM','alpha3Code' => 'ZMB','callingCodes' => '260','capital' => 'Lusaka','region' => 'Africa','subregion' => 'Eastern Africa','population' => '15933883','latlng' => '-15,30','demonym' => 'Zambian','timezones' => 'UTC+02:00','currency_name' => 'Zambian kwacha','currency_code' => 'ZMW','currency_symbol' => 'ZK','flag' => 'https://restcountries.eu/data/zmb.svg','address_format' => ''),
  array('ID' => '250','name' => 'Zimbabwe','slug' => 'zimbabwe','alpha2Code' => 'ZW','alpha3Code' => 'ZWE','callingCodes' => '263','capital' => 'Harare','region' => 'Africa','subregion' => 'Eastern Africa','population' => '14240168','latlng' => '-20,30','demonym' => 'Zimbabwean','timezones' => 'UTC+02:00','currency_name' => 'Botswana pula','currency_code' => 'BWP','currency_symbol' => 'P','flag' => 'https://restcountries.eu/data/zwe.svg','address_format' => '')
);
			
			return $country;
		}
		
	} /* class end */
} /* if end */

function run_blsd_regional_product_listing_func() {
	BLSDRegionalProductListing::instance();
}
run_blsd_regional_product_listing_func();