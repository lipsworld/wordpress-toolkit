<?php
namespace WordPress_ToolKit;

/**
  * A helper class for getting/setting values from WordPress object cache, if
  *    available.
  *
  * @since 0.1.0
  */
class ObjectCache extends ToolKit {

  /**
    * Retrieves value from cache, if enabled/present, else returns value
    *    generated by callback().
    *
    * @param string $key Key value of cache to retrieve
    * @param function $callback Result to return/set if does not exist in cache
    * @return string Cached value of key
    * @since 0.1.0
    */
  public function get_object( $key = null, $callback, $cache_disabled = false ) {

    $object_cache_group = self::$config->get( 'object_cache/group' ) ? self::$config->get( 'object_cache/group' ) : sanitize_title( self::$config->get( 'data/Name' ) );
    if( is_multisite() ) $object_cache_group .= '_' . get_current_blog_id();
    $object_cache_expire = ( is_int( self::$config->get( 'object_cache/expire_hours' ) ) ? self::$config->get( 'object_cache/expire_hours' ) : 24 ) * 86400; // Default to 24 hours

    $result = null;

    // Set key variable
    $object_cache_key =  $key . ( is_multisite() ? '_' . get_current_blog_id() : '' );

    // Try to get the value of the cache
    if( !$cache_disabled ) {
      $result = wp_cache_get( $object_cache_key, $object_cache_group );
      if( $result && is_serialized( $result ) ) $result = unserialize($result);
    }

    // If result wasn't found/returned and/or caching is disabled, set & return the value from $callback
    if( !$result ) {
      $result = $callback();
      if( !$cache_disabled ) wp_cache_set( $object_cache_key, ( is_array( $result ) || is_object( $result ) ? serialize( $result ) : $result ), $object_cache_group, $object_cache_expire);
    }

    return $result;

  }

  /**
    * Flushes the object cache, if enabled. Parameters are not used but are
    *    when passed by 'publish_post' action hook.
    *
    * Example usage: Cache::flush();
    *
    * @param int $ID The ID of the post being published
    * @param WP_Post The post object that is being published
    * @return mixed Returns success as JSON string if called by AJAX,
    *    else true/false
    * @since 0.1.0
    */
  public function flush($ID = null, $post = null) {

    $result = array('success' => true);

    try {
      wp_cache_flush();
    } catch (Exception $e) {
      $result = array('success' => false, 'message' => $e->getMessage());
    }

    if( defined('DOING_AJAX') && DOING_AJAX ) {
      echo json_encode($result);
      die();
    }
    return $result['success'];

  }

}
