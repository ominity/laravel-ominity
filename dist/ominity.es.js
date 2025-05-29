const r = {
  config: {},
  init() {
    document.querySelectorAll("form.ominity-form[data-form]").forEach((e) => {
      var t;
      const n = e.getAttribute("data-form") || "", a = e.getAttribute("data-recaptcha"), d = (t = document.querySelector('meta[name="recaptcha-site-key"]')) == null ? void 0 : t.getAttribute("content");
      e.addEventListener("submit", (o) => {
        const s = new CustomEvent("form:submit", { detail: { formId: n, recaptchaVersion: a }, cancelable: !0 });
        if (!e.dispatchEvent(s)) {
          o.preventDefault();
          return;
        }
        if (r.config.disableSubmitDuringRequest !== !1) {
          const c = e.querySelector('button[type="submit"]');
          c && (c.disabled = !0, c.classList.add("disabled"));
        }
        if (a === "v3") {
          if (typeof grecaptcha > "u") {
            console.warn("reCAPTCHA v3 is not loaded.");
            return;
          }
          let c = e.querySelector('input[name="g-recaptcha-response"]');
          c || (c = document.createElement("input"), c.type = "hidden", c.name = "g-recaptcha-response", e.appendChild(c)), c.value === "" && (o.preventDefault(), grecaptcha.ready(() => {
            grecaptcha.execute(d, { action: "submit" }).then((u) => {
              c.value = u, r.submitForm(e, n);
            });
          }));
        } else e.getAttribute("data-role") === "ajax" && (o.preventDefault(), r.submitForm(e, n));
      });
    });
  },
  submitForm(e, n) {
    e.getAttribute("data-role") === "ajax" ? this.handleFormAjaxSubmit(e, n) : e.submit();
  },
  handleFormAjaxSubmit(e, n) {
    var d;
    const a = new FormData(e);
    fetch(e.action, {
      method: e.method || "POST",
      body: a,
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        "X-CSRF-TOKEN": ((d = document.querySelector('meta[name="csrf-token"]')) == null ? void 0 : d.getAttribute("content")) || ""
      }
    }).then((t) => t.json()).then((t) => {
      if (e.getAttribute("data-recaptcha") === "v3") {
        const s = e.querySelector('input[name="g-recaptcha-response"]');
        s && (s.value = "");
      }
      if (e.querySelectorAll(".has-validation").forEach((s) => s.classList.remove("has-validation")), e.querySelectorAll(".is-invalid").forEach((s) => s.classList.remove("is-invalid")), e.querySelectorAll(".invalid-feedback").forEach((s) => s.remove()), e.querySelectorAll(".alert.alert-success").forEach((s) => s.remove()), e.dispatchEvent(new CustomEvent("form:submitted", { detail: { formId: n, data: t } })), t.success) {
        e.reset();
        const s = new CustomEvent("form:success", { detail: { formId: n, data: t }, cancelable: !0 });
        if (!!e.dispatchEvent(s)) {
          const i = document.createElement("div");
          i.className = "alert alert-success", i.textContent = (t == null ? void 0 : t.message) || "Your form was successfully submitted.", e.prepend(i);
        }
      } else if (t.errors) {
        const s = new CustomEvent("form:errors", { detail: { formId: n, data: t }, cancelable: !0 });
        !e.dispatchEvent(s) || r.handleFormErrors(e, t.errors);
      } else
        e.dispatchEvent(new CustomEvent("form:unknown", { detail: { formId: n, data: t } }));
    }).catch((t) => {
      console.error("Form submit error:", t), e.dispatchEvent(new CustomEvent("form:fail", { detail: { formId: n, error: t } }));
    });
  },
  handleFormErrors(e, n) {
    e.querySelectorAll(".has-validation").forEach((a) => a.classList.remove("has-validation")), e.querySelectorAll(".is-invalid").forEach((a) => a.classList.remove("is-invalid")), e.querySelectorAll(".invalid-feedback").forEach((a) => a.remove()), Object.entries(n).forEach(([a, d]) => {
      const t = d[0];
      let o = `[name="${a}"], [name="${a}[]"]`;
      if (a.includes(".")) {
        const l = a.replace(/\./g, "][");
        o += `, [name="${l}"]`;
      }
      const s = e.querySelectorAll(o);
      if (s.length > 0) {
        let l = !1;
        if (s.forEach((i) => {
          if (i.type === "hidden" || i.offsetParent === null)
            r.showToast(t, "danger"), l = !0;
          else {
            i.classList.add("is-invalid");
            const u = i.closest(".input-group");
            u && u.classList.add("has-validation");
          }
        }), !l) {
          const i = s[0];
          i.type === "checkbox" || i.type === "radio" ? s[s.length - 1].insertAdjacentHTML("afterend", `<div class="invalid-feedback">${t}</div>`) : i.insertAdjacentHTML("afterend", `<div class="invalid-feedback">${t}</div>`);
        }
      } else
        r.showToast(t, "danger");
    });
  },
  showToast(e, n = "danger") {
    typeof r.config.toastHandler == "function" ? r.config.toastHandler({ type: n, message: e }) : typeof window.$ < "u" && typeof window.$.fn.showToast == "function" ? window.$.fn.showToast({ type: n, title: e }) : console.warn(`[${n.toUpperCase()}] ${e}`);
  }
};
window.OminityForms = r;
export {
  r as OminityForms
};
