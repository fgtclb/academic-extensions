const formSelector = "[data-academic-persons-inline-edit]";
const fieldSelector = ".academic-persons-inline-edit__field";

const getFieldValue = (field) => {
  if (field instanceof HTMLInputElement && field.type === "checkbox") {
    return field.checked;
  }
  return field.value;
};

const showStatus = (statusElement, message, type) => {
  statusElement.classList.remove(
    "d-none",
    "alert-info",
    "alert-success",
    "alert-danger"
  );
  statusElement.classList.add(`alert-${type}`);
  statusElement.textContent = message;
};

const clearValidationErrors = (fields) => {
  fields.forEach((field) => {
    field.classList.remove("is-invalid");
    const feedback = field.closest(".mb-3, .form-check")?.querySelector(".invalid-feedback");
    if (feedback !== null && feedback !== void 0) {
      feedback.textContent = "";
    }
  });
};

const showValidationErrors = (fields, errors) => {
  Object.entries(errors).forEach(([propertyPath, messages]) => {
    const propertyName = propertyPath.split(".").pop();
    const field = fields.find((candidate) => candidate.name === propertyName);
    if (field === void 0) {
      return;
    }

    field.classList.add("is-invalid");
    const feedback = field.closest(".mb-3, .form-check")?.querySelector(".invalid-feedback");
    if (feedback !== null && feedback !== void 0) {
      feedback.textContent = Array.isArray(messages) ? messages.join(" ") : String(messages);
    }
  });
};

document.querySelectorAll(formSelector).forEach((form) => {
  const statusElement = form.querySelector("[data-inline-profile-status]");
  if (!(form instanceof HTMLFormElement) || !(statusElement instanceof HTMLElement)) {
    return;
  }

  const fields = Array.from(form.querySelectorAll(fieldSelector)).filter(
    (field) => field instanceof HTMLInputElement
      || field instanceof HTMLSelectElement
      || field instanceof HTMLTextAreaElement
  );
  const initialValues = new Map(
    fields.map((field) => [field.name, getFieldValue(field)])
  );
  const submitButton = form.querySelector('button[type="submit"]');

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    clearValidationErrors(fields);

    const data = {};
    fields.forEach((field) => {
      const value = getFieldValue(field);
      if (initialValues.get(field.name) !== value) {
        data[field.name] = value;
      }
    });

    if (Object.keys(data).length === 0) {
      showStatus(statusElement, form.dataset.messageUnchanged ?? "", "info");
      return;
    }

    const profileUid = Number.parseInt(form.dataset.profileUid ?? "", 10);
    const updateUrl = form.dataset.updateUrl;
    if (!Number.isInteger(profileUid) || profileUid <= 0 || updateUrl === void 0) {
      showStatus(statusElement, form.dataset.messageError ?? "", "danger");
      return;
    }

    form.setAttribute("aria-busy", "true");
    if (submitButton instanceof HTMLButtonElement) {
      submitButton.disabled = true;
    }
    showStatus(statusElement, form.dataset.messageSaving ?? "", "info");

    try {
      const response = await fetch(updateUrl, {
        method: "POST",
        credentials: "same-origin",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          profile: profileUid,
          data
        })
      });
      const result = await response.json().catch(() => null);

      if (!response.ok || result?.success !== true) {
        if (result?.errors !== void 0 && typeof result.errors === "object") {
          showValidationErrors(fields, result.errors);
          showStatus(statusElement, form.dataset.messageValidation ?? "", "danger");
        } else {
          showStatus(
            statusElement,
            result?.message ?? form.dataset.messageError ?? "",
            "danger"
          );
        }
        return;
      }

      Object.keys(data).forEach((propertyName) => {
        initialValues.set(propertyName, data[propertyName]);
      });
      showStatus(statusElement, form.dataset.messageSuccess ?? "", "success");
    } catch {
      showStatus(statusElement, form.dataset.messageError ?? "", "danger");
    } finally {
      form.removeAttribute("aria-busy");
      if (submitButton instanceof HTMLButtonElement) {
        submitButton.disabled = false;
      }
    }
  });
});
