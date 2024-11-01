<?php
/*
Plugin Name: Software News
Description: Show the latest software related news on your site.
Version:     1.0
Author:      UpdateStar
Author URI:  https://news.updatestar.com/
License:     GPL2
*/

if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

require_once(plugin_dir_path(__FILE__) . '/Date.php'); 

class WP_Widget_UpdateStar_News extends WP_Widget {

    const URL = 'https://news.updatestar.com/feed';
    const TITLE = 'Software News';
    const WIDGET_ID = 'updatestar-news';

	/**
	 * Sets up a new widget instance.
	 *
	 * @since 2.8.0
	 */
	public function __construct() {
		$widget_ops = array(
			'description' => __( 'The latest software related news.' ),
			'customize_selective_refresh' => true,
		);
		$control_ops = array( 'width' => 400, 'height' => 200 );
		parent::__construct( self::WIDGET_ID, __( 'Software News' ), $widget_ops, $control_ops );
	}

	/**
	 * Outputs the content for the current widget instance.
	 *
	 * @since 2.8.0
	 *
	 * @param array $args     Display arguments including 'before_title', 'after_title',
	 *                        'before_widget', and 'after_widget'.
	 * @param array $instance Settings for the current widget instance.
	 */
	public function widget( $args, $instance ) {
		if ( isset($instance['error']) && $instance['error'] )
			return;

		$url = self::URL;

		// self-url destruction sequence
		if ( in_array( untrailingslashit( $url ), array( site_url(), home_url() ) ) )
			return;

        $rss = fetch_feed($url);
        $rss->registry->register("Parse_Date", "UpdateStar_SimplePie_Parse_Date");
		$title = $instance['title'];
		$desc = '';
		$link = '';

		if ( ! is_wp_error($rss) ) {
			$desc = esc_attr(strip_tags(@html_entity_decode($rss->get_description(), ENT_QUOTES, get_option('blog_charset'))));
			if ( empty($title) )
				$title = self::TITLE;
			$link = strip_tags( $rss->get_permalink() );
			while ( stristr($link, 'http') != $link )
				$link = substr($link, 1);
		}

		if ( empty( $title ) ) {
			$title = ! empty( $desc ) ? $desc : __( 'Unknown Feed' );
		}

		/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$url = strip_tags( $url );
		if ( $title )
			$title = '<a class="rsswidget" href="' . esc_url( $url ) . '"></a> <a class="rsswidget" href="' . esc_url( $link ) . '">'. esc_html( $title ) . '</a>';

        $args['before_widget'] = str_replace("widget_" . self::WIDGET_ID, "widget_rss widget_" . self::WIDGET_ID, $args['before_widget']);

		echo $args['before_widget'];
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
		wp_widget_rss_output( $rss, $instance );
		echo $args['after_widget'];

		if ( ! is_wp_error($rss) )
			$rss->__destruct();
		unset($rss);
	}

	/**
	 * Handles updating settings for the current widget instance.
	 *
	 * @since 2.8.0
	 *
	 * @param array $new_instance New settings for this instance as input by the user via
	 *                            WP_Widget::form().
	 * @param array $old_instance Old settings for this instance.
	 * @return array Updated settings to save.
	 */
	public function update( $new_instance, $old_instance ) {
        $new_instance['url'] = self::URL;
		$testurl = ( isset( $new_instance['url'] ) && ( !isset( $old_instance['url'] ) || ( $new_instance['url'] != $old_instance['url'] ) ) );
		return wp_widget_rss_process( $new_instance, $testurl );
	}

	/**
	 * Outputs the settings form for the widget.
	 *
	 * @since 2.8.0
	 *
	 * @param array $instance Current settings.
	 */
	public function form( $instance ) {
		if ( empty( $instance ) ) {
			$instance = array( 'title' => self::TITLE, 'url' => self::URL, 'items' => 10, 'error' => false, 'show_summary' => 1, 'show_author' => 1, 'show_date' => 1 );
		}
		$instance['number'] = $this->number;

		$this->widget_form( $instance );
    }
    
    /**
     * Display widget options form.
     *
     * The options for what fields are displayed for the RSS form are all booleans
     * and are as follows: 'url', 'title', 'items', 'show_summary', 'show_author',
     * 'show_date'.
     *
     * @since 2.5.0
     *
     * @param array|string $args Values for input fields.
     * @param array $inputs Override default display options.
     */
    function widget_form( $args, $inputs = null ) {
        $default_inputs = array( 'url' => false, 'title' => true, 'items' => true, 'show_summary' => true, 'show_author' => true, 'show_date' => true );
        $inputs = wp_parse_args( $inputs, $default_inputs );

        $args['title'] = isset( $args['title'] ) ? $args['title'] : '';
        $args['url'] = isset( $args['url'] ) ? $args['url'] : '';
        $args['items'] = isset( $args['items'] ) ? (int) $args['items'] : 0;

        if ( $args['items'] < 1 || 20 < $args['items'] ) {
            $args['items'] = 10;
        }

        $args['show_summary']   = isset( $args['show_summary'] ) ? (int) $args['show_summary'] : (int) $inputs['show_summary'];
        $args['show_author']    = isset( $args['show_author'] ) ? (int) $args['show_author'] : (int) $inputs['show_author'];
        $args['show_date']      = isset( $args['show_date'] ) ? (int) $args['show_date'] : (int) $inputs['show_date'];

        if ( ! empty( $args['error'] ) ) {
            echo '<p class="widget-error"><strong>' . __( 'RSS Error:' ) . '</strong> ' . $args['error'] . '</p>';
        }

        $esc_number = esc_attr( $args['number'] );
        if ( $inputs['title'] ) : ?>
        <p><label for="<?php echo self::WIDGET_ID; ?>-title-<?php echo $esc_number; ?>"><?php _e( 'Give the widget a title (optional):' ); ?></label>
        <input class="widefat" id="<?php echo self::WIDGET_ID; ?>-title-<?php echo $esc_number; ?>" name="widget-<?php echo self::WIDGET_ID; ?>[<?php echo $esc_number; ?>][title]" type="text" value="<?php echo esc_attr( $args['title'] ); ?>" /></p>
    <?php endif; if ( $inputs['items'] ) : ?>
        <p><label for="<?php echo self::WIDGET_ID; ?>-items-<?php echo $esc_number; ?>"><?php _e( 'How many items would you like to display?' ); ?></label>
        <select id="<?php echo self::WIDGET_ID; ?>-items-<?php echo $esc_number; ?>" name="widget-<?php echo self::WIDGET_ID; ?>[<?php echo $esc_number; ?>][items]">
        <?php
        for ( $i = 1; $i <= 20; ++$i ) {
            echo "<option value='$i' " . selected( $args['items'], $i, false ) . ">$i</option>";
        }
        ?>
        </select></p>
    <?php endif; if ( $inputs['show_summary'] ) : ?>
        <p><input id="<?php echo self::WIDGET_ID; ?>-show-summary-<?php echo $esc_number; ?>" name="widget-<?php echo self::WIDGET_ID; ?>[<?php echo $esc_number; ?>][show_summary]" type="checkbox" value="1" <?php checked( $args['show_summary'] ); ?> />
        <label for="<?php echo self::WIDGET_ID; ?>-show-summary-<?php echo $esc_number; ?>"><?php _e( 'Display item content?' ); ?></label></p>
    <?php endif; if ( $inputs['show_author'] ) : ?>
        <p><input id="<?php echo self::WIDGET_ID; ?>-show-author-<?php echo $esc_number; ?>" name="widget-<?php echo self::WIDGET_ID; ?>[<?php echo $esc_number; ?>][show_author]" type="checkbox" value="1" <?php checked( $args['show_author'] ); ?> />
        <label for="<?php echo self::WIDGET_ID; ?>-show-author-<?php echo $esc_number; ?>"><?php _e( 'Display item author if available?' ); ?></label></p>
    <?php endif; if ( $inputs['show_date'] ) : ?>
        <p><input id="<?php echo self::WIDGET_ID; ?>-show-date-<?php echo $esc_number; ?>" name="widget-<?php echo self::WIDGET_ID; ?>[<?php echo $esc_number; ?>][show_date]" type="checkbox" value="1" <?php checked( $args['show_date'] ); ?>/>
        <label for="<?php echo self::WIDGET_ID; ?>-show-date-<?php echo $esc_number; ?>"><?php _e( 'Display item date?' ); ?></label></p>
    <?php
        endif;
        foreach ( array_keys($default_inputs) as $input ) :
            if ( 'hidden' === $inputs[$input] ) :
                $id = str_replace( '_', '-', $input );
    ?>
        <input type="hidden" id="<?php echo self::WIDGET_ID; ?>-<?php echo esc_attr( $id ); ?>-<?php echo $esc_number; ?>" name="widget-<?php echo self::WIDGET_ID; ?>[<?php echo $esc_number; ?>][<?php echo esc_attr( $input ); ?>]" value="<?php echo esc_attr( $args[ $input ] ); ?>" />
    <?php
            endif;
        endforeach;
    }

    function is_wide_widget_in_customizer($is_wide, $widget_id) {
        return preg_match("/" . preg_quote(self::WIDGET_ID) . "-\d+/", $widget_id) ? false : $is_wide;
    }
}

// register widget
add_action('widgets_init', create_function('', 'return register_widget("WP_Widget_UpdateStar_News");'));
add_filter('is_wide_widget_in_customizer', 'WP_Widget_UpdateStar_News::is_wide_widget_in_customizer', 10, 2);