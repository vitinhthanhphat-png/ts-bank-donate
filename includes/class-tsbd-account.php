<?php
defined( 'ABSPATH' ) || exit;

class TSBD_Account {

    // ─── Read ────────────────────────────────────────────────────────────────

    public static function all(): array {
        return get_option( TSBD_OPTION_ACCOUNTS, [] );
    }

    public static function active(): array {
        return array_values(
            array_filter( self::all(), fn( $a ) => ! empty( $a['active'] ) )
        );
    }

    public static function find( string $id ): ?array {
        foreach ( self::all() as $account ) {
            if ( $account['id'] === $id ) return $account;
        }
        return null;
    }

    public static function get_by_ids( array $ids ): array {
        return array_values(
            array_filter( self::all(), fn( $a ) => in_array( $a['id'], $ids, true ) )
        );
    }

    // ─── Write ───────────────────────────────────────────────────────────────

    public static function create( array $data ): array {
        $data['id']     = uniqid( 'acc_' );
        $data['order']  = count( self::all() );
        $account        = self::sanitize( $data );
        $accounts       = self::all();
        $accounts[]     = $account;
        update_option( TSBD_OPTION_ACCOUNTS, $accounts );
        return $account;
    }

    public static function update( string $id, array $data ): bool {
        $accounts = self::all();
        $found    = false;
        $accounts = array_map( function ( $a ) use ( $id, $data, &$found ) {
            if ( $a['id'] === $id ) {
                $found = true;
                return array_merge( $a, self::sanitize( $data ) );
            }
            return $a;
        }, $accounts );

        if ( ! $found ) return false;
        return update_option( TSBD_OPTION_ACCOUNTS, $accounts );
    }

    public static function delete( string $id ): bool {
        $account = self::find( $id );
        if ( $account && ! empty( $account['attachment_id'] ) ) {
            wp_delete_attachment( (int) $account['attachment_id'], true );
        }
        $accounts = array_values(
            array_filter( self::all(), fn( $a ) => $a['id'] !== $id )
        );
        return update_option( TSBD_OPTION_ACCOUNTS, $accounts );
    }

    public static function reorder( array $ordered_ids ): void {
        $accounts = self::all();
        $index    = [];
        foreach ( $ordered_ids as $pos => $id ) {
            $index[ $id ] = (int) $pos;
        }
        foreach ( $accounts as &$a ) {
            if ( isset( $index[ $a['id'] ] ) ) {
                $a['order'] = $index[ $a['id'] ];
            }
        }
        usort( $accounts, fn( $x, $y ) => $x['order'] <=> $y['order'] );
        update_option( TSBD_OPTION_ACCOUNTS, $accounts );
    }

    public static function set_attachment( string $id, int $attachment_id ): void {
        self::update( $id, [ 'attachment_id' => $attachment_id ] );
    }

    // ─── Sanitize ─────────────────────────────────────────────────────────────

    private static function sanitize( array $data ): array {
        $out = [];

        if ( isset( $data['id'] ) )
            $out['id'] = sanitize_key( $data['id'] );

        if ( isset( $data['type'] ) )
            $out['type'] = in_array( $data['type'], [ 'bank', 'momo' ], true ) ? $data['type'] : 'bank';

        if ( isset( $data['label'] ) )
            $out['label'] = sanitize_text_field( $data['label'] );

        if ( isset( $data['active'] ) )
            $out['active'] = (bool) $data['active'];

        if ( isset( $data['order'] ) )
            $out['order'] = absint( $data['order'] );

        // Only update box_template when explicitly provided
        if ( isset( $data['box_template'] ) )
            $out['box_template'] = in_array( $data['box_template'], [ 'modern', 'minimal', 'glass', 'classic' ], true )
                ? $data['box_template'] : '';

        if ( isset( $data['default_note'] ) )
            $out['default_note'] = sanitize_text_field( $data['default_note'] );

        if ( isset( $data['default_amount'] ) )
            $out['default_amount'] = absint( $data['default_amount'] );

        if ( isset( $data['attachment_id'] ) )
            $out['attachment_id'] = absint( $data['attachment_id'] );

    // Bank-specific — only when type is supplied or already known
        $type = $data['type'] ?? null;
        if ( $type === 'bank' || ( $type === null && isset( $data['bank_bin'] ) ) ) {
            if ( isset( $data['bank_bin'] ) )
                $out['bank_bin'] = sanitize_text_field( $data['bank_bin'] );
            if ( isset( $data['bank_short'] ) )
                $out['bank_short'] = sanitize_text_field( $data['bank_short'] );
            if ( isset( $data['bank_logo'] ) )
                $out['bank_logo'] = esc_url_raw( $data['bank_logo'] );
            if ( isset( $data['account_no'] ) )
                $out['account_no'] = sanitize_text_field( $data['account_no'] );
            if ( isset( $data['account_name'] ) )
                $out['account_name'] = strtoupper( sanitize_text_field( $data['account_name'] ) );
            // Only update vietqr_template when explicitly provided
            if ( isset( $data['vietqr_template'] ) )
                $out['vietqr_template'] = in_array( $data['vietqr_template'], [ 'compact', 'compact2', 'qr_only', 'print' ], true )
                    ? $data['vietqr_template'] : 'compact2';
        }

        // MoMo-specific
        if ( $type === 'momo' ) {
            if ( isset( $data['phone'] ) )
                $out['phone'] = sanitize_text_field( $data['phone'] );
            if ( isset( $data['account_name'] ) )
                $out['account_name'] = sanitize_text_field( $data['account_name'] );
        }

        return $out;
    }
}
