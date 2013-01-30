package EntryCFBasenames::Tags;
use strict;

sub _hdlr_entrycustomfieldbasenames {
    my ( $ctx, $args, $cond ) = @_;
    my $all = $args->{ 'all' };
    my $var = $args->{ 'var' } || 'customfield_basename';
    my $app = MT->instance();
    my $blog = $ctx->stash( 'blog' );
    my $entry = $ctx->stash( 'entry' );
    my $class = $entry->class;
    my $column = $class . '_prefs';
    my $prefs;
    if ( $entry->has_column( 'prefs' ) ) {
        # PowerCMS.pack
        $prefs = $entry->prefs;
    }
    if (! $prefs ) {
        if ( ( ref $app ) =~ /^MT::App::/ ) {
            if ( my $user = $app->user ) {
                $prefs = $user->permissions( $entry->blog_id )->$column;
            }
        }
    }
    if (! $prefs ) {
        my $search = '%publish_post%';
        if ( $class eq 'page' ) {
            $search = '%manage_pages%';
        }
        my $perm = MT->model( 'permission' )->load( { blog_id => $entry->blog_id,
                                                      permissions => { like => $search } },
                                                    { limit => 1 } );
        if ( $perm ) {
            $prefs = $perm->$column;
        }
    }
    return '' unless $prefs;
    my @fields = split( /,/, $prefs );
    my @customfields;
    my $tokens  = $ctx->stash( 'tokens' );
    my $builder = $ctx->stash( 'builder' );
    my $vars = $ctx->{ __stash }{ vars } ||= +{};
    for my $field ( @fields ) {
        if ( $field =~ /^customfield_/ ) {
            $field =~ s/^customfield_/field./;
        } else {
            if (! $all ) {
                $field = '';
            }
        }
        if ( ( $field && $entry->has_column( $field ) ) || ( $field eq 'tags' ) ) {
            $field =~ s/^field\.//;
            push ( @customfields, $field );
        }
    }
    my $res = '';
    my $counter = 1;
    for my $basename ( @customfields ) {
        local $vars->{ $var } = $basename;
        local $vars->{ __first__ } = $counter == 1;
        local $vars->{ __last__ } = $counter == scalar( @customfields );
        local $vars->{ __counter__ } = $counter;
        $counter++;
        my $out = $builder->build( $ctx, $tokens, $cond );
        $res .= $out;
    }
    $res;
}

1;