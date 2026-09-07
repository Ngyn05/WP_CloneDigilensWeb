<?php
/**
 * Order details table shown in emails - 100% Vietnamese & No Images
 *
 * @package DigiLens
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$text_align = is_rtl() ? 'right' : 'left';
$order_date = $order->get_date_created() ? $order->get_date_created()->date_i18n( 'd/m/Y H:i' ) : current_time( 'd/m/Y H:i' );
?>

<h3 style="color: #0f172a; font-size: 16px; font-weight: 800; margin: 20px 0 10px 0;">
    Chi tiết đơn yêu cầu 
    <span style="font-size: 13px; font-weight: 600; color: #0284c7;">(#DL-<?php echo esc_html( $order->get_order_number() ); ?> &bull; <?php echo esc_html( $order_date ); ?>)</span>
</h3>

<div style="margin-bottom: 25px;">
    <table cellspacing="0" cellpadding="8" style="width: 100%; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; border: 1px solid #e2e8f0; border-collapse: collapse;" border="1">
        <thead>
            <tr style="background: #f8fafc; color: #334155;">
                <th scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>; padding: 10px 12px; font-size: 13px; font-weight: 700; border: 1px solid #e2e8f0;">Sản phẩm / Yêu cầu</th>
                <th scope="col" style="text-align:center; padding: 10px 12px; font-size: 13px; font-weight: 700; border: 1px solid #e2e8f0; width: 70px;">SL</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ( $order->get_items() as $item ) {
                ?>
                <tr>
                    <td style="text-align:<?php echo esc_attr( $text_align ); ?>; border: 1px solid #e2e8f0; padding: 10px 12px; font-size: 13px; color: #0f172a;">
                        <?php echo esc_html( $item->get_name() ); ?>
                        <?php do_action( 'woocommerce_order_item_meta_start', $item->get_id(), $item, $order, $plain_text ); ?>
                        <?php wc_display_item_meta( $item, array( 'label_before' => '<div style="font-size:12px;color:#64748b;">', 'label_after' => ': ', 'separator' => '<br>', 'echo' => true ) ); ?>
                        <?php do_action( 'woocommerce_order_item_meta_end', $item->get_id(), $item, $order, $plain_text ); ?>
                    </td>
                    <td style="text-align:center; border: 1px solid #e2e8f0; padding: 10px 12px; font-size: 13px; color: #334155;"><?php echo esc_html( $item->get_quantity() ); ?></td>
                </tr>
                <?php
            }
            ?>
        </tbody>
        <tfoot>
            <?php
            if ( $order->get_customer_note() ) {
                ?>
                <tr>
                    <td colspan="2" style="text-align:left; border: 1px solid #e2e8f0; padding: 10px 12px; font-size: 12px; color: #334155; line-height: 1.5;"><strong style="color:#0284c7;">Ghi chú &amp; Thông tin khách:</strong><br><?php echo wp_kses_post( nl2br( wptexturize( $order->get_customer_note() ) ) ); ?></td>
                </tr>
                <?php
            }
            ?>
        </tfoot>
    </table>
</div>

<?php do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email ); ?>
