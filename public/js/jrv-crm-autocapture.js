/**
 * JRV CRM - Universal 1-Click Website Auto-Capture & Instant Payment Gateway Engine
 * Works on ANY Website (WordPress, HTML, PHP, React, Next.js, Webflow, Wix, Shopify, Squarespace)
 */
(function() {
  'use strict';

  var currentScript = document.currentScript || document.querySelector('script[src*="jrv-crm-autocapture.js"]');
  var scriptUrl = currentScript ? currentScript.src : window.location.origin + '/js/jrv-crm-autocapture.js';
  var endpointUrl = new URL('/api/v1/integration/auto-capture', scriptUrl).href;
  var paymentBaseUrl = new URL('/embed/pay', scriptUrl).href;
  
  var tenantToken = currentScript ? (currentScript.getAttribute('data-crm-token') || currentScript.getAttribute('data-tenant-id') || '7') : '7';
  var businessName = currentScript ? (currentScript.getAttribute('data-business-name') || 'Admissions Dekho') : 'CRM Inquiry';
  var brandColor = currentScript ? (currentScript.getAttribute('data-brand-color') || '#dc2626') : '#dc2626';
  var buttonText = currentScript ? (currentScript.getAttribute('data-button-text') || '🎓 Quick Inquiry') : '🎓 Quick Inquiry';
  var enableWidget = currentScript ? (currentScript.getAttribute('data-widget') === 'true' || currentScript.getAttribute('data-widget') === 'bubble' || currentScript.getAttribute('data-widget') === 'lead') : false;
  var enablePayWidget = currentScript ? (currentScript.getAttribute('data-widget') === 'pay' || currentScript.getAttribute('data-widget') === 'payment') : false;

  function extractFormData(form) {
    var formData = new FormData(form);
    var data = {
      website_source: window.location.href,
      page_title: document.title,
      tenant_token: tenantToken,
      tenant_id: tenantToken
    };

    formData.forEach(function(val, key) {
      var cleanKey = key.toLowerCase().replace(/[^a-z0-9_]/g, '_');
      data[cleanKey] = val;

      // Smart mappings for common form field names
      if (cleanKey.includes('email') || cleanKey === 'e_mail' || cleanKey === 'mail') data.email = val;
      if (cleanKey.includes('phone') || cleanKey.includes('mobile') || cleanKey.includes('contact') || cleanKey === 'tel' || cleanKey === 'whatsapp') data.phone = val;
      if (cleanKey.includes('first') || cleanKey === 'fname') data.first_name = val;
      if (cleanKey.includes('last') || cleanKey === 'lname') data.last_name = val;
      if (cleanKey === 'name' || cleanKey === 'fullname' || cleanKey === 'full_name' || cleanKey === 'your_name') data.name = val;
      if (cleanKey.includes('gender') || cleanKey === 'sex') data.gender = val;
      if (cleanKey.includes('caste') || cleanKey.includes('community')) data.caste = val;
      if (cleanKey.includes('city') || cleanKey === 'location') data.city = val;
      if (cleanKey.includes('state')) data.state = val;
      if (cleanKey.includes('msg') || cleanKey.includes('message') || cleanKey.includes('inquiry') || cleanKey.includes('course') || cleanKey.includes('requirement')) data.message = val;
    });

    return data;
  }

  function sendLeadToCRM(payload, callback) {
    if (!payload.email && !payload.phone && !payload.name && !payload.first_name) {
      if (callback) callback({ success: false, message: 'Please provide at least a name, phone, or email.' });
      return;
    }

    payload.tenant_token = payload.tenant_token || tenantToken;
    payload.tenant_id = payload.tenant_id || tenantToken;
    payload.website_source = payload.website_source || window.location.href;

    try {
      if (window.fetch) {
        fetch(endpointUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify(payload),
          keepalive: true
        }).then(function(res) { return res.json(); })
          .then(function(res) {
            if (res.success) {
              console.log('[JRV CRM] Lead synced successfully. Ref:', res.member_code || res.id);
              if (callback) callback(res);
            } else {
              if (callback) callback(res);
            }
          }).catch(function(err) {
            console.warn('[JRV CRM] Auto-sync notice:', err);
            if (callback) callback({ success: false, error: err });
          });
      }
    } catch (e) {
      console.warn('[JRV CRM] Sync exception:', e);
      if (callback) callback({ success: false, error: e });
    }
  }

  function attachToForms() {
    var forms = document.querySelectorAll('form:not([data-jrv-attached])');
    forms.forEach(function(form) {
      if (form.id === 'jrv-crm-widget-form') return;
      form.setAttribute('data-jrv-attached', 'true');

      form.addEventListener('submit', function() {
        var payload = extractFormData(form);
        sendLeadToCRM(payload);
      });
    });

    // Attach to 1-Click Pay Buttons on the website
    var payButtons = document.querySelectorAll('[data-crm-pay]:not([data-jrv-pay-attached]), .jrv-crm-pay-btn:not([data-jrv-pay-attached])');
    payButtons.forEach(function(btn) {
      btn.setAttribute('data-jrv-pay-attached', 'true');
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        var amount = btn.getAttribute('data-crm-pay') || btn.getAttribute('data-amount') || 1000;
        var purpose = btn.getAttribute('data-purpose') || btn.getAttribute('data-item') || 'Online Fee Payment';
        openPaymentModal(amount, purpose);
      });
    });
  }

  // --- FLOATING INQUIRY WIDGET ---
  function injectFloatingWidget() {
    if (document.getElementById('jrv-crm-floating-btn')) return;

    var btn = document.createElement('div');
    btn.id = 'jrv-crm-floating-btn';
    btn.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:999999;cursor:pointer;font-family:system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;box-shadow:0 10px 25px rgba(0,0,0,0.25);transition:all 0.3s ease;display:flex;align-items:center;gap:8px;padding:12px 20px;border-radius:50px;background:' + brandColor + ';color:#ffffff;font-size:13px;font-weight:800;letter-spacing:-0.2px;user-select:none;';
    btn.innerHTML = '<span>' + buttonText + '</span>';
    btn.onmouseover = function() { btn.style.transform = 'scale(1.05) translateY(-2px)'; };
    btn.onmouseout = function() { btn.style.transform = 'scale(1) translateY(0)'; };

    var modal = document.createElement('div');
    modal.id = 'jrv-crm-floating-modal';
    modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(15,23,42,0.6);backdrop-filter:blur(4px);z-index:1000000;display:none;align-items:center;justify-content:center;padding:16px;font-family:system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;';
    
    modal.innerHTML = 
      '<div style="background:#ffffff;border-radius:24px;max-width:440px;width:100%;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);overflow:hidden;position:relative;">' +
        '<div style="background:' + brandColor + ';padding:20px 24px;color:#ffffff;display:flex;align-items:center;justify-content:space-between;">' +
          '<div>' +
            '<h3 style="margin:0;font-size:16px;font-weight:900;letter-spacing:-0.3px;">' + businessName + '</h3>' +
            '<p style="margin:2px 0 0 0;font-size:11px;opacity:0.9;font-weight:600;">Direct Enquiry & Instant Callback</p>' +
          '</div>' +
          '<button id="jrv-crm-close-btn" style="background:rgba(255,255,255,0.2);border:none;color:#ffffff;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:14px;font-weight:bold;display:flex;align-items:center;justify-content:center;">✕</button>' +
        '</div>' +
        '<form id="jrv-crm-widget-form" style="padding:20px 24px;display:flex;flex-direction:column;gap:12px;margin:0;">' +
          '<div>' +
            '<label style="display:block;font-size:11px;font-weight:700;color:#334155;margin-bottom:4px;">Full Name *</label>' +
            '<input type="text" name="name" required placeholder="e.g. Rahul Sharma" style="width:100%;box-sizing:border-box;padding:10px 14px;border:1px solid #cbd5e1;border-radius:12px;font-size:13px;outline:none;" />' +
          '</div>' +
          '<div>' +
            '<label style="display:block;font-size:11px;font-weight:700;color:#334155;margin-bottom:4px;">Phone / WhatsApp Number *</label>' +
            '<input type="tel" name="phone" required placeholder="e.g. +91 9876543210" style="width:100%;box-sizing:border-box;padding:10px 14px;border:1px solid #cbd5e1;border-radius:12px;font-size:13px;outline:none;" />' +
          '</div>' +
          '<div>' +
            '<label style="display:block;font-size:11px;font-weight:700;color:#334155;margin-bottom:4px;">Email Address</label>' +
            '<input type="email" name="email" placeholder="e.g. rahul@example.com" style="width:100%;box-sizing:border-box;padding:10px 14px;border:1px solid #cbd5e1;border-radius:12px;font-size:13px;outline:none;" />' +
          '</div>' +
          '<div>' +
            '<label style="display:block;font-size:11px;font-weight:700;color:#334155;margin-bottom:4px;">Inquiry / Course / Requirement</label>' +
            '<textarea name="message" rows="2" placeholder="Tell us what you are looking for..." style="width:100%;box-sizing:border-box;padding:10px 14px;border:1px solid #cbd5e1;border-radius:12px;font-size:13px;outline:none;resize:none;"></textarea>' +
          '</div>' +
          '<button type="submit" id="jrv-submit-btn" style="background:' + brandColor + ';color:#ffffff;border:none;padding:12px;border-radius:14px;font-size:13px;font-weight:800;cursor:pointer;margin-top:6px;box-shadow:0 4px 12px rgba(0,0,0,0.15);">Submit Enquiry 🚀</button>' +
          '<div id="jrv-crm-success-msg" style="display:none;background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;padding:12px;border-radius:12px;font-size:12px;font-weight:700;text-align:center;">' +
            '✅ Thank you! We received your request and will contact you shortly.' +
          '</div>' +
        '</form>' +
      '</div>';

    document.body.appendChild(btn);
    document.body.appendChild(modal);

    btn.addEventListener('click', function() { modal.style.display = 'flex'; });
    var closeBtn = document.getElementById('jrv-crm-close-btn');
    if (closeBtn) closeBtn.addEventListener('click', function() { modal.style.display = 'none'; });
    modal.addEventListener('click', function(e) { if (e.target === modal) modal.style.display = 'none'; });

    var form = document.getElementById('jrv-crm-widget-form');
    if (form) {
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        var submitBtn = document.getElementById('jrv-submit-btn');
        var successBox = document.getElementById('jrv-crm-success-msg');
        if (submitBtn) { submitBtn.innerText = 'Submitting...'; submitBtn.disabled = true; }

        var payload = extractFormData(form);
        sendLeadToCRM(payload, function(res) {
          if (submitBtn) submitBtn.style.display = 'none';
          if (successBox) successBox.style.display = 'block';
          setTimeout(function() {
            modal.style.display = 'none';
            form.reset();
            if (submitBtn) {
              submitBtn.innerText = 'Submit Enquiry 🚀';
              submitBtn.style.display = 'block';
              submitBtn.disabled = false;
            }
            if (successBox) successBox.style.display = 'none';
          }, 3000);
        });
      });
    }
  }

  // --- 1-CLICK EMBEDDED PAYMENT MODAL ENGINE ---
  function openPaymentModal(amount, purpose) {
    var existingModal = document.getElementById('jrv-crm-pay-modal');
    if (existingModal) existingModal.remove();

    var targetUrl = paymentBaseUrl + '?tenant_id=' + encodeURIComponent(tenantToken) + 
      '&amount=' + encodeURIComponent(amount || 1000) + 
      '&purpose=' + encodeURIComponent(purpose || 'Online Fee Payment');

    var payModal = document.createElement('div');
    payModal.id = 'jrv-crm-pay-modal';
    payModal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(15,23,42,0.7);backdrop-filter:blur(6px);z-index:9999999;display:flex;align-items:center;justify-content:center;padding:12px;font-family:system-ui,-apple-system,sans-serif;box-sizing:border-box;';

    payModal.innerHTML = 
      '<div style="background:#ffffff;border-radius:24px;max-width:920px;width:100%;height:90vh;max-height:850px;box-shadow:0 30px 60px -12px rgba(0,0,0,0.35);overflow:hidden;position:relative;display:flex;flex-direction:column;">' +
        '<div style="background:#0f172a;color:#ffffff;padding:12px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #1e293b;">' +
          '<div style="display:flex;align-items:center;gap:8px;">' +
            '<span style="font-size:16px;">💳</span>' +
            '<strong style="font-size:13px;font-weight:800;">' + businessName + ' Secure Payment</strong>' +
          '</div>' +
          '<button id="jrv-pay-close-btn" style="background:#334155;border:none;color:#ffffff;width:28px;height:28px;border-radius:50%;cursor:pointer;font-size:12px;font-weight:bold;display:flex;align-items:center;justify-content:center;">✕</button>' +
        '</div>' +
        '<iframe src="' + targetUrl + '" style="width:100%;height:100%;border:none;flex:1;" allow="payment; camera;"></iframe>' +
      '</div>';

    document.body.appendChild(payModal);

    var closeBtn = document.getElementById('jrv-pay-close-btn');
    if (closeBtn) closeBtn.addEventListener('click', function() { payModal.remove(); });
    payModal.addEventListener('click', function(e) { if (e.target === payModal) payModal.remove(); });
  }

  // Global helper for manual programmatic payment and lead dispatch
  window.JRV_CRM = {
    sendLead: sendLeadToCRM,
    init: attachToForms,
    openWidget: function() {
      var modal = document.getElementById('jrv-crm-floating-modal');
      if (modal) modal.style.display = 'flex';
      else injectFloatingWidget();
    },
    openPayment: openPaymentModal
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      attachToForms();
      if (enableWidget) injectFloatingWidget();
    });
  } else {
    attachToForms();
    if (enableWidget) injectFloatingWidget();
  }

  setInterval(attachToForms, 3000);
})();
