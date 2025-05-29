const c = {
  config: {},
  init() {
    document.querySelectorAll("form.ominity-form[data-form]").forEach((e) => {
      var t;
      const a = e.getAttribute("data-form") || "", i = e.getAttribute("data-recaptcha"), o = (t = document.querySelector('meta[name="recaptcha-site-key"]')) == null ? void 0 : t.getAttribute("content");
      e.addEventListener("submit", (r) => {
        const s = new CustomEvent("form:submit", { detail: { formId: a, recaptchaVersion: i }, cancelable: !0 });
        if (!e.dispatchEvent(s)) {
          r.preventDefault();
          return;
        }
        if (c.disableSubmitButtons(e), i === "v3") {
          if (typeof grecaptcha > "u") {
            console.warn("reCAPTCHA v3 is not loaded.");
            return;
          }
          let n = e.querySelector('input[name="g-recaptcha-response"]');
          n || (n = document.createElement("input"), n.type = "hidden", n.name = "g-recaptcha-response", e.appendChild(n)), n.value === "" && (r.preventDefault(), grecaptcha.ready(() => {
            grecaptcha.execute(o, { action: "submit" }).then((d) => {
              n.value = d, c.submitForm(e, a);
            });
          }));
        } else e.getAttribute("data-role") === "ajax" && (r.preventDefault(), c.submitForm(e, a));
      });
    });
  },
  submitForm(e, a) {
    e.getAttribute("data-role") === "ajax" ? this.handleFormAjaxSubmit(e, a) : e.submit();
  },
  handleFormAjaxSubmit(e, a) {
    var o;
    const i = new FormData(e);
    e.querySelectorAll(".alert.alert-success").forEach((t) => t.remove()), fetch(e.action, {
      method: e.method || "POST",
      body: i,
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        "X-CSRF-TOKEN": ((o = document.querySelector('meta[name="csrf-token"]')) == null ? void 0 : o.getAttribute("content")) || ""
      }
    }).then((t) => t.json()).then((t) => {
      if (e.getAttribute("data-recaptcha") === "v3") {
        const s = e.querySelector('input[name="g-recaptcha-response"]');
        s && (s.value = "");
      }
      if (c.enableSubmitButtons(e), e.querySelectorAll(".has-validation").forEach((s) => s.classList.remove("has-validation")), e.querySelectorAll(".is-invalid").forEach((s) => s.classList.remove("is-invalid")), e.querySelectorAll(".invalid-feedback").forEach((s) => s.remove()), e.dispatchEvent(new CustomEvent("form:submitted", { detail: { formId: a, data: t } })), t.success) {
        e.reset();
        const s = new CustomEvent("form:success", { detail: { formId: a, data: t }, cancelable: !0 });
        if (!!e.dispatchEvent(s)) {
          const n = document.createElement("div");
          n.className = "alert alert-success", n.textContent = (t == null ? void 0 : t.message) || "Your form was successfully submitted.", e.prepend(n);
        }
      } else if (t.errors) {
        const s = new CustomEvent("form:errors", { detail: { formId: a, data: t }, cancelable: !0 });
        !e.dispatchEvent(s) || c.handleFormErrors(e, t.errors);
      } else
        e.dispatchEvent(new CustomEvent("form:unknown", { detail: { formId: a, data: t } }));
    }).catch((t) => {
      console.error("Form submit error:", t), e.dispatchEvent(new CustomEvent("form:fail", { detail: { formId: a, error: t } }));
    });
  },
  handleFormErrors(e, a) {
    e.querySelectorAll(".has-validation").forEach((i) => i.classList.remove("has-validation")), e.querySelectorAll(".is-invalid").forEach((i) => i.classList.remove("is-invalid")), e.querySelectorAll(".invalid-feedback").forEach((i) => i.remove()), Object.entries(a).forEach(([i, o]) => {
      const t = o[0];
      let r = `[name="${i}"], [name="${i}[]"]`;
      if (i.includes(".")) {
        const l = i.replace(/\./g, "][");
        r += `, [name="${l}"]`;
      }
      const s = e.querySelectorAll(r);
      if (s.length > 0) {
        let l = !1;
        if (s.forEach((n) => {
          if (n.type === "hidden" || n.offsetParent === null)
            c.showToast(t, "danger"), l = !0;
          else {
            n.classList.add("is-invalid");
            const u = n.closest(".input-group");
            u && u.classList.add("has-validation");
          }
        }), !l) {
          const n = s[0];
          n.type === "checkbox" || n.type === "radio" ? s[s.length - 1].insertAdjacentHTML("afterend", `<div class="invalid-feedback">${t}</div>`) : n.insertAdjacentHTML("afterend", `<div class="invalid-feedback">${t}</div>`);
        }
      } else
        c.showToast(t, "danger");
    });
  },
  showToast(e, a = "danger") {
    typeof c.config.toastHandler == "function" ? c.config.toastHandler({ type: a, message: e }) : typeof window.$ < "u" && typeof window.$.fn.showToast == "function" ? window.$.fn.showToast({ type: a, title: e }) : console.warn(`[${a.toUpperCase()}] ${e}`);
  },
  enableSubmitButtons(e) {
    if (c.config.disableSubmitDuringRequest !== !1) {
      const i = e.getAttribute("data-form") || "", o = new CustomEvent("form:submit-enabled", { detail: { formId: i }, cancelable: !0 });
      !e.dispatchEvent(o) || e.querySelectorAll('button[type="submit"]').forEach((s) => {
        s.disabled = !1, s.classList.remove("disabled");
      });
    }
  },
  disableSubmitButtons(e) {
    if (c.config.disableSubmitDuringRequest !== !1) {
      const i = e.getAttribute("data-form") || "", o = new CustomEvent("form:submit-disabled", { detail: { formId: i }, cancelable: !0 });
      !e.dispatchEvent(o) || e.querySelectorAll('button[type="submit"]').forEach((s) => {
        s.disabled = !0, s.classList.add("disabled");
      });
    }
  }
};
window.OminityForms = c;
export {
  c as OminityForms
};
