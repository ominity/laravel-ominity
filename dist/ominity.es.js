const o = {
  config: {},
  init() {
    document.querySelectorAll("form.ominity-form[data-form]").forEach((e) => {
      var t;
      const n = e.getAttribute("data-form") || "", s = e.getAttribute("data-recaptcha"), c = (t = document.querySelector('meta[name="recaptcha-site-key"]')) == null ? void 0 : t.getAttribute("content");
      e.addEventListener("submit", (r) => {
        if (e.dispatchEvent(new CustomEvent("form:submit", { detail: { formId: n, recaptchaVersion: s } })), s === "v3") {
          if (typeof grecaptcha > "u") {
            console.warn("reCAPTCHA v3 is not loaded.");
            return;
          }
          let a = e.querySelector('input[name="g-recaptcha-response"]');
          a || (a = document.createElement("input"), a.type = "hidden", a.name = "g-recaptcha-response", e.appendChild(a)), a.value === "" && (r.preventDefault(), grecaptcha.execute(c, { action: "submit" }).then((l) => {
            a.value = l, o.submitForm(e, n);
          }));
        }
        e.getAttribute("data-role") === "ajax" && (r.preventDefault(), o.submitForm(e, n));
      });
    });
  },
  submitForm(e, n) {
    e.getAttribute("data-role") === "ajax" ? this.handleFormAjaxSubmit(e, n) : e.submit();
  },
  handleFormAjaxSubmit(e, n) {
    var c;
    const s = new FormData(e);
    fetch(e.action, {
      method: e.method || "POST",
      body: s,
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        "X-CSRF-TOKEN": ((c = document.querySelector('meta[name="csrf-token"]')) == null ? void 0 : c.getAttribute("content")) || ""
      }
    }).then((t) => t.json()).then((t) => {
      if (e.getAttribute("data-recaptcha") === "v3") {
        const a = e.querySelector('input[name="g-recaptcha-response"]');
        a && (a.value = "");
      }
      e.dispatchEvent(new CustomEvent("form:submitted", { detail: { formId: n, data: t } })), t.success ? (e.reset(), e.dispatchEvent(new CustomEvent("form:success", { detail: { formId: n, data: t } }))) : t.errors ? (o.handleFormErrors(e, t.errors), e.dispatchEvent(new CustomEvent("form:error", { detail: { formId: n, data: t } }))) : e.dispatchEvent(new CustomEvent("form:unknown", { detail: { formId: n, data: t } }));
    }).catch((t) => {
      console.error("Form submit error:", t), e.dispatchEvent(new CustomEvent("form:fail", { detail: { formId: n, error: t } }));
    });
  },
  handleFormErrors(e, n) {
    e.querySelectorAll(".has-validation").forEach((s) => s.classList.remove("has-validation")), e.querySelectorAll(".is-invalid").forEach((s) => s.classList.remove("is-invalid")), e.querySelectorAll(".invalid-feedback").forEach((s) => s.remove()), Object.entries(n).forEach(([s, c]) => {
      const t = c[0];
      let r = `[name="${s}"], [name="${s}[]"]`;
      if (s.includes(".")) {
        const l = s.replace(/\./g, "][");
        r += `, [name="${l}"]`;
      }
      const a = e.querySelectorAll(r);
      if (a.length > 0) {
        let l = !1;
        if (a.forEach((i) => {
          if (i.type === "hidden" || i.offsetParent === null)
            o.showToast(t, "danger"), l = !0;
          else {
            i.classList.add("is-invalid");
            const d = i.closest(".input-group");
            d && d.classList.add("has-validation");
          }
        }), !l) {
          const i = a[0];
          i.type === "checkbox" || i.type === "radio" ? a[a.length - 1].insertAdjacentHTML("afterend", `<div class="invalid-feedback">${t}</div>`) : i.insertAdjacentHTML("afterend", `<div class="invalid-feedback">${t}</div>`);
        }
      } else
        o.showToast(t, "danger");
    });
  },
  showToast(e, n = "danger") {
    typeof o.config.toastHandler == "function" ? o.config.toastHandler({ type: n, message: e }) : typeof window.$ < "u" && typeof window.$.fn.showToast == "function" ? window.$.fn.showToast({ type: n, title: e }) : console.warn(`[${n.toUpperCase()}] ${e}`);
  }
};
window.OminityForms = o;
export {
  o as OminityForms
};
