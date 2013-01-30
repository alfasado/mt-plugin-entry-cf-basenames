<?php
class EntryCFBasenames extends MTPlugin {
    var $app;
    var $registry = array(
        'name' => 'EntryCFBasenames',
        'id'   => 'EntryCFBasenames',
        'key'  => 'entrycfbasenames',
        'author_name' => 'Alfasado Inc.',
        'author_link' => 'http://alfasado.net/',
        'version' => '0.1',
        'description' => 'Entry/Page CustomField basenames loop by order.',
        'tags' => array(
            'block'    => array( 'entrycfbasenames' => '_hdlr_entrycustomfieldbasenames',
                                 'pagecfbasenames' => '_hdlr_entrycustomfieldbasenames',
                                 'entrycustomfieldbasenames' => '_hdlr_entrycustomfieldbasenames',
                                 'pagecustomfieldbasenames' => '_hdlr_entrycustomfieldbasenames', ),
        ),
    );

    function _hdlr_entrycustomfieldbasenames ( $args, $content, &$ctx, &$repeat ) {
        $app = $this->app;
        $var = $args[ 'var' ];
        if (! $var ) $var = 'customfield_basename';
        $localvars = array( $var );
        if (! isset( $content ) ) {
            $all = $args[ 'all' ];
            $ctx->localize( $localvars );
            $entry = $ctx->stash( 'entry' );
            $prefs;
            if ( $entry->has_column( 'prefs' ) ) {
                // PowerCMS.pack
                $prefs = $entry->prefs;
            }
            if ( $prefs ) {
                $class = $entry->class;
                $column = $class . '_prefs';
                $search = '%publish_post%';
                if ( $class == 'page' ) {
                    $search = '%manage_pages%';
                }
                $perm = $app->load( 'Permission', array( 'blog_id' => $entry->blog_id,
                                                         'permission' => array( 'like' => $search )
                                                          ), array( 'limit' => 1 ) );
                if ( $perm ) {
                    $prefs = $perm->$column;
                }
            }
            if (! $prefs ) {
                $repeat = FALSE;
                return '';
            }
            $fields = explode( ',', $prefs );
            $customfields = array();
            foreach ( $fields as $field ) {
                if ( preg_match( '/^customfield_/', $field ) ) {
                    $field = preg_replace( '/^customfield_/', 'field.', $field );
                } else {
                    if (! $all ) {
                        $field = '';
                    }
                }
                if ( $field ) {
                    if ( preg_match( '/^field\./', $field ) ) {
                        $field = preg_replace( '/^field\./', '', $field );
                        array_push( $customfields, $field );
                    } else {
                        if ( ( $entry->has_column( $field ) ) || ( $field == 'tags' ) ) {
                            array_push( $customfields, $field );
                        }
                    }
                }
            }
            $ctx->stash( '__customfields__', $customfields );
            $ctx->stash( '__max__', count( $customfields ) - 1 );
            $ctx->stash( '__counter__', 0 );
        }
        $max = $ctx->stash( '__max__' );
        $counter = $ctx->stash( '__counter__' ) + 1;
        $ctx->stash( '__counter__', $counter );
        $customfields = $ctx->stash( '__customfields__' );
        if ( $counter <= $max ) {
            $ctx->__stash[ 'vars' ][ '__counter__' ] = $counter;
            $ctx->__stash[ 'vars' ][ '__first__' ] = $counter == 1;
            $ctx->__stash[ 'vars' ][ '__last__' ] = $counter == $max;
            $ctx->__stash[ 'vars' ][ $var ] = $customfields[ $counter ];
            $repeat = TRUE;
            return $content;
        } else {
            $ctx->restore( $localvars );
            $repeat = FALSE;
            return $content;
        }
    }
}

?>