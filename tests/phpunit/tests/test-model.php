<?php

class Model_Test extends PLL_UnitTestCase {

	static function wpSetUpBeforeClass() {
		parent::wpSetUpBeforeClass();

		self::create_language( 'en_US' );
		self::create_language( 'fr_FR' );
	}

	function test_languages_list() {
		self::$polylang->model->post->register_taxonomy(); // needed otherwise posts are not counted

		$this->assertEquals( array( 'en', 'fr' ), self::$polylang->model->get_languages_list( array( 'fields' => 'slug' ) ) );
		$this->assertEquals( array( 'English', 'Français' ), self::$polylang->model->get_languages_list( array( 'fields' => 'name' ) ) );
		$this->assertEquals( array(), self::$polylang->model->get_languages_list( array( 'hide_empty' => true ) ) );

		$post_id = $this->factory->post->create();
		self::$polylang->model->post->set_language( $post_id, 'en' );

		$this->assertEquals( array( 'en' ), self::$polylang->model->get_languages_list( array( 'fields' => 'slug', 'hide_empty' => true ) ) );
	}

	function test_term_exists() {
		$parent = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'parent' ) );
		self::$polylang->model->term->set_language( $parent, 'en' );
		$child = $this->factory->term->create( array( 'taxonomy' => 'category', 'name' => 'child', 'parent' => $parent ) );
		self::$polylang->model->term->set_language( $child, 'en' );

		$this->assertEquals( $parent, self::$polylang->model->term_exists( 'parent', 'category', 0, 'en' ) );
		$this->assertEquals( $child, self::$polylang->model->term_exists( 'child', 'category', 0, 'en' ) );
		$this->assertEquals( $child, self::$polylang->model->term_exists( 'child', 'category', $parent, 'en' ) );
		$this->assertEmpty( self::$polylang->model->term_exists( 'parent', 'category', 0, 'fr' ) );
		$this->assertEmpty( self::$polylang->model->term_exists( 'child', 'category', 0, 'fr' ) );
		$this->assertEmpty( self::$polylang->model->term_exists( 'child', 'category', $parent, 'fr' ) );
	}

	function test_count_posts() {
		$en = $this->factory->post->create();
		self::$polylang->model->post->set_language( $en, 'en' );

		$en = $this->factory->post->create( array( 'post_date' => '2007-09-04 00:00:00', 'post_author' => 1 ) );
		set_post_format( $en, 'aside' );
		self::$polylang->model->post->set_language( $en, 'en' );

		$fr = $this->factory->post->create();
		self::$polylang->model->post->set_language( $fr, 'fr' );

		$fr = $this->factory->post->create( array( 'post_date' => '2007-09-04 00:00:00', 'post_author' => 1, 'post_status' => 'draft' ) );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		$fr = $this->factory->post->create( array( 'post_date' => '2007-09-04 00:00:00', 'post_author' => 1 ) );
		set_post_format( $fr, 'aside' );
		self::$polylang->model->post->set_language( $fr, 'fr' );

		$language = self::$polylang->model->get_language( 'fr' );
		$this->assertEquals( 2, self::$polylang->model->count_posts( $language ) );
		$this->assertEquals( 1, self::$polylang->model->count_posts( $language, array( 'post_format' => 'post-format-aside' ) ) );
		$this->assertEquals( 1, self::$polylang->model->count_posts( $language, array( 'year' => 2007 ) ) );
		$this->assertEquals( 1, self::$polylang->model->count_posts( $language, array( 'year' => 2007, 'monthnum' => 9 ) ) );
		$this->assertEquals( 1, self::$polylang->model->count_posts( $language, array( 'year' => 2007, 'monthnum' => 9, 'day' => 4 ) ) );
		$this->assertEquals( 1, self::$polylang->model->count_posts( $language, array( 'm' => 2007 ) ) );
		$this->assertEquals( 1, self::$polylang->model->count_posts( $language, array( 'm' => 200709 ) ) );
		$this->assertEquals( 1, self::$polylang->model->count_posts( $language, array( 'm' => 20070904 ) ) );
		$this->assertEquals( 1, self::$polylang->model->count_posts( $language, array( 'author' => 1 ) ) );
		$this->assertEquals( 1, self::$polylang->model->count_posts( $language, array( 'author_name' => 'admin' ) ) );
	}

	function test_backward_compat_1_8() {
		$lang_object = self::$polylang->model->get_language( 'en' );

		// post
		$en = $this->factory->post->create();
		@self::$polylang->model->set_post_language( $en, 'en' );
		$this->assertEquals( 'en', self::$polylang->model->post->get_language( $en )->slug );
		$this->assertEquals( 'en' , @self::$polylang->model->get_post_language( $en )->slug );

		$fr = $this->factory->post->create();
		self::$polylang->model->post->set_language( $fr, 'fr' );
		@self::$polylang->model->save_translations( 'post', $en, compact( 'en', 'fr' ) );
		$this->assertEquals( $en, @self::$polylang->model->get_post( $fr, 'en' ) );
		$this->assertEquals( $fr, @self::$polylang->model->get_post( $fr, 'fr' ) );
		$this->assertEquals( $en, @self::$polylang->model->get_translation( 'post', $fr, 'en' ) );
		$this->assertEquals( self::$polylang->model->post->get_translations( $fr ),  @self::$polylang->model->get_translations( 'post', $fr ) );
		$this->assertEquals( self::$polylang->model->post->get_objects_in_language( $lang_object ), @self::$polylang->model->get_objects_in_language( $lang_object, 'post' ) );

		$this->assertEquals( self::$polylang->model->post->get_object_term( $en, 'language' ), @self::$polylang->model->get_object_term( $en, 'language' ) );
		$this->assertEquals( self::$polylang->model->post->get_object_term( $en, 'post_translations' ), @self::$polylang->model->get_object_term( $en, 'post_translations' ) );

		$this->assertEquals( @self::$polylang->model->join_clause( 'post' ), self::$polylang->model->post->join_clause() );
		$this->assertEquals( @self::$polylang->model->where_clause( 'en', 'post' ), self::$polylang->model->post->where_clause( 'en' ) );

		// term
		$en = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		self::$polylang->model->term->set_language( $en, 'en' );
		$this->assertEquals( 'en', self::$polylang->model->term->get_language( $en )->slug );
		$this->assertEquals( 'en', @self::$polylang->model->get_term_language( $en )->slug );

		$fr = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		@self::$polylang->model->set_term_language( $fr, 'fr' );
		@self::$polylang->model->save_translations( 'term', $en, compact( 'en', 'fr' ) );
		$this->assertEquals( $en, @self::$polylang->model->get_term( $fr, 'en' ) );
		$this->assertEquals( $fr, @self::$polylang->model->get_term( $fr, 'fr' ) );
		$this->assertEquals( $en, @self::$polylang->model->get_translation( 'term', $fr, 'en' ) );
		$this->assertEquals( self::$polylang->model->term->get_translations( $fr ),  @self::$polylang->model->get_translations( 'term', $fr ) );
		$this->assertEquals( self::$polylang->model->term->get_objects_in_language( $lang_object ), @self::$polylang->model->get_objects_in_language( $lang_object, 'term' ) );

		$this->assertEquals( self::$polylang->model->term->get_object_term( $en, 'term_language' ), @self::$polylang->model->get_object_term( $en, 'term_language' ) );
		$this->assertEquals( self::$polylang->model->term->get_object_term( $en, 'term_translations' ), @self::$polylang->model->get_object_term( $en, 'term_translations' ) );

		$this->assertEquals( @self::$polylang->model->join_clause( 'term' ), self::$polylang->model->term->join_clause() );
		$this->assertEquals( @self::$polylang->model->where_clause( 'en', 'term' ), self::$polylang->model->term->where_clause( 'en' ) );
	}

	function test_translated_post_types() {
		// deactivate the cache
		self::$polylang->model->cache = $this->getMockBuilder( 'PLL_Cache' )->getMock();
		self::$polylang->model->cache->method( 'get' )->willReturn( false );

		self::$polylang->options['media_support'] = 0;

		$this->assertTrue( self::$polylang->model->is_translated_post_type( 'post' ) );
		$this->assertTrue( self::$polylang->model->is_translated_post_type( 'page' ) );
		$this->assertFalse( self::$polylang->model->is_translated_post_type( 'nav_menu_item' ) );
		$this->assertFalse( self::$polylang->model->is_translated_post_type( 'attachment' ) );

		self::$polylang->options['media_support'] = 1;
		$this->assertTrue( self::$polylang->model->is_translated_post_type( 'attachment' ) );

		self::$polylang->model->cache = new PLL_Cache();
	}

	function test_translated_taxonomies() {
		$this->assertTrue( self::$polylang->model->is_translated_taxonomy( 'category' ) );
		$this->assertTrue( self::$polylang->model->is_translated_taxonomy( 'post_tag' ) );
		$this->assertFalse( self::$polylang->model->is_translated_taxonomy( 'post_format' ) );
		$this->assertFalse( self::$polylang->model->is_translated_taxonomy( 'nav_menu' ) );
		$this->assertFalse( self::$polylang->model->is_translated_taxonomy( 'language' ) );
	}

	function test_filtered_taxonomies() {
		$this->assertTrue( self::$polylang->model->is_filtered_taxonomy( 'post_format' ) );
		$this->assertFalse( self::$polylang->model->is_filtered_taxonomy( 'category' ) );
		$this->assertFalse( self::$polylang->model->is_filtered_taxonomy( 'post_tag' ) );
		$this->assertFalse( self::$polylang->model->is_filtered_taxonomy( 'nav_menu' ) );
		$this->assertFalse( self::$polylang->model->is_filtered_taxonomy( 'language' ) );
	}
}

