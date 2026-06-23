
<div id="voucherCapture" data-id-sale="{ID}" data-phone-customer="{PhoneCustomer}" data-customer-name="{Nombre Cliente}">
    <div style="max-width:560px;margin:22px auto;font-family:'Segoe UI',Arial,sans-serif;background:#f4f6f8;padding:14px;">
        <table width="100%" cellspacing="0" cellpadding="0" style="background:#ffffff; border-radius:16px; overflow:hidden; color:#1f2933; border-collapse:collapse; box-shadow:0 8px 24px rgba(0,0,0,0.08);">
            <tr>
                <td style="padding:20px 22px;border-bottom:1px solid #e5e7eb;">
                    <table width="100%" cellspacing="0" cellpadding="0">
                        <tr>
                            <td style="width:40px;"></td>
                            <td align="center">
                                <img src="{Logo}" alt="Logo" style="display:block;margin:0 auto 4px;max-width:130px;width:100%;height:auto;">
                                <p style="margin:6px 0 0;font-size:11px;font-weight:700;color:#6b7280;letter-spacing:1.6px;text-transform:uppercase;text-align:center;">Comprobante de venta</p>
                            </td>
                            <td align="right" valign="top" style="width:40px;">
                                <a href="javascript:void(0)" onclick="shareVoucher(this)" title="Compartir" style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:{ColorTicketShareBg};border:1px solid {ColorTicketShareBorder};text-decoration:none;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="{ColorTicketAccent}" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.6" y1="13.5" x2="15.4" y2="17.5"/><line x1="15.4" y1="6.5" x2="8.6" y2="10.5"/></svg>
                                </a>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style="padding:20px 24px 12px;">
                    <p style="margin:0;font-size:15px;color:#374151;">Hola,</p>
                    <strong style="display:block;margin-top:6px;font-size:17px;font-weight:700;color:{ColorTicketText};">{Nombre Cliente}</strong>
                </td>
            </tr>
            <tr>
                <td style="padding:4px 24px 14px;text-align:center;">
                    <span style="display:inline-block;font-size:12px;color:#9ca3af;line-height:1.5;max-width:420px;">{TituloRifa}</span>
                </td>
            </tr>
            <tr>
                <td style="padding:0 24px 18px;">
                    <table width="100%" style="font-size:14px;">
                        <tr><td style="color:#6b7280;font-weight:600;font-size:13px;">ID</td><td align="right" style="font-weight:700;">#{ID}</td></tr>
                        <tr><td style="color:#6b7280;font-weight:600;font-size:13px;">Fecha</td><td align="right" style="font-weight:700;">{Fecha}</td></tr>
                        <tr><td style="color:#6b7280;font-weight:600;font-size:13px;">Cant. números</td><td align="right" style="font-weight:700;">{Cantidad}</td></tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style="padding:0 24px 24px;">
                    <p style="margin:12px 0 8px;font-size:12px;font-weight:800;color:#374151;letter-spacing:1.5px;text-transform:uppercase;text-align:center;">Código de seguridad</p>
                    <div data-codigo="{Codigo}" style="background:{ColorTicketCodeBg};border:1px solid {ColorTicketCodeBorder};border-radius:12px;padding:14px;text-align:center;font-family:monospace;font-size:16px;font-weight:700;color:{ColorTicketText};letter-spacing:1px;">{Codigo}</div>
                </td>
            </tr>
            <tr>
                <td align="center" style="padding:0 20px 26px;">
                    <div style="background:{ColorTicketNumbersBg};border:2px dashed {ColorTicketAccent};border-radius:16px;padding:26px;">
                        <p style="margin:0 0 16px;font-size:13px;font-weight:800;color:{ColorTicketText};letter-spacing:2px;text-transform:uppercase;">Tus números</p>
                        <div style="text-align:center;">{NumerosHTML}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</div>
