const o = {
  config: {},
  init() {
    document.querySelectorAll("form.ominity-form[data-form]").forEach((e) => {
      var a;
      const n = e.getAttribute("data-form") || "", i = e.getAttribute("data-recaptcha"), c = (a = document.querySelector('meta[name="recaptcha-site-key"]')) == null ? void 0 : a.getAttribute("content");
      e.addEventListener("submit", (r) => {
        if (e.dispatchEvent(new CustomEvent("form:submit", { detail: { formId: n, recaptchaVersion: i } })), i === "v3") {
          if (typeof grecaptcha > "u") {
            console.warn("reCAPTCHA v3 is not loaded.");
            return;
          }
          let t = e.querySelector('input[name="g-recaptcha-response"]');
          t || (t = document.createElement("input"), t.type = "hidden", t.name = "g-recaptcha-response", e.appendChild(t)), t.value === "" && (r.preventDefault(), grecaptcha.ready(() => {
            grecaptcha.execute(c, { action: "submit" }).then((l) => {
              t.value = l, o.submitForm(e, n);
            });
          }));
        } else e.getAttribute("data-role") === "ajax" && (r.preventDefault(), o.submitForm(e, n));
      });
    });
  },
  submitForm(e, n) {
    e.getAttribute("data-role") === "ajax" ? this.handleFormAjaxSubmit(e, n) : e.submit();
  },
  handleFormAjaxSubmit(e, n) {
    var c;
    const i = new FormData(e);
    fetch(e.action, {
      method: e.method || "POST",
      body: i,
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        "X-CSRF-TOKEN": ((c = document.querySelector('meta[name="csrf-token"]')) == null ? void 0 : c.getAttribute("content")) || ""
      }
    }).then((a) => a.json()).then((a) => {
      if (e.getAttribute("data-recaptcha") === "v3") {
        const t = e.querySelector('input[name="g-recaptcha-response"]');
        t && (t.value = "");
      }
      e.dispatchEvent(new CustomEvent("form:submitted", { detail: { formId: n, data: a } })), a.success ? (e.querySelectorAll(".has-validation").forEach((t) => t.classList.remove("has-validation")), e.querySelectorAll(".is-invalid").forEach((t) => t.classList.remove("is-invalid")), e.querySelectorAll(".invalid-feedback").forEach((t) => t.remove()), e.reset(), e.dispatchEvent(new CustomEvent("form:success", { detail: { formId: n, data: a } }))) : a.errors ? (o.handleFormErrors(e, a.errors), e.dispatchEvent(new CustomEvent("form:error", { detail: { formId: n, data: a } }))) : e.dispatchEvent(new CustomEvent("form:unknown", { detail: { formId: n, data: a } }));
    }).catch((a) => {
      console.error("Form submit error:", a), e.dispatchEvent(new CustomEvent("form:fail", { detail: { formId: n, error: a } }));
    });
  },
  handleFormErrors(e, n) {
    e.querySelectorAll(".has-validation").forEach((i) => i.classList.remove("has-validation")), e.querySelectorAll(".is-invalid").forEach((i) => i.classList.remove("is-invalid")), e.querySelectorAll(".invalid-feedback").forEach((i) => i.remove()), Object.entries(n).forEach(([i, c]) => {
      const a = c[0];
      let r = `[name="${i}"], [name="${i}[]"]`;
      if (i.includes(".")) {
        const l = i.replace(/\./g, "][");
        r += `, [name="${l}"]`;
      }
      const t = e.querySelectorAll(r);
      if (t.length > 0) {
        let l = !1;
        if (t.forEach((s) => {
          if (s.type === "hidden" || s.offsetParent === null)
            o.showToast(a, "danger"), l = !0;
          else {
            s.classList.add("is-invalid");
            const d = s.closest(".input-group");
            d && d.classList.add("has-validation");
          }
        }), !l) {
          const s = t[0];
          s.type === "checkbox" || s.type === "radio" ? t[t.length - 1].insertAdjacentHTML("afterend", `<div class="invalid-feedback">${a}</div>`) : s.insertAdjacentHTML("afterend", `<div class="invalid-feedback">${a}</div>`);
        }
      } else
        o.showToast(a, "danger");
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
