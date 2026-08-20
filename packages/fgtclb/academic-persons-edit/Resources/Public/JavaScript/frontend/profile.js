const formSelector = "[data-academic-persons-inline-edit]";
const editButtonSelector = "[data-academic-persons-inline-edit-activate-btn]";
const fieldSelector = ".academic-persons-inline-edit__field";
const formElement = document.querySelector(formSelector);
const templateInlineEditBtn = document.getElementById("templateInlineEditBtn");
const templateInlineEditNew = document.getElementById("templateInlineEditNew");
const statusValues = {
  danger: {
    title: formElement.dataset.messageErrorTitle ?? "",
    message: formElement.dataset.messageErrorMessage ?? "",
    class: "bg-danger",
  },
  error: {
    title: formElement.dataset.messageErrorTitle ?? "",
    message: formElement.dataset.messageErrorMessage ?? "",
    class: "bg-danger",
  },
  success: {
    title: formElement.dataset.messageSuccessTitle ?? "",
    message: formElement.dataset.messageSuccessMessage ?? "",
    class: "bg-success",
  },
  info: {
    title: formElement.dataset.messageInfoTitle ?? "",
    message: formElement.dataset.messageInfoMessage ?? "",
    class: "bg-info",
  },
  warning: {
    title: formElement.dataset.messageWarningTitle ?? "",
    message: formElement.dataset.messageWarningMessage ?? "",
    class: "bg-warning",
  },
};

const getFieldValue = (field) => {
  if (field instanceof HTMLInputElement && field.type === "checkbox") {
    return field.checked;
  }
  return field.value;
};

const showStatus = (type) => {
  const statusToastSelector = "[data-ie-status-toast]";
  const statusToast = document.querySelector(statusToastSelector);
  const statusToastTitleElement = statusToast.querySelector(".status-title");
  const statusToastMessageElement =
    statusToast.querySelector(".status-message");
  statusToast.classList.remove(
    "d-none",
    "bg-info",
    "bg-success",
    "bg-danger",
    "bg-warning",
  );
  statusToast.classList.add(statusValues[type]?.class);
  statusToastTitleElement.innerHTML = statusValues[type].title;
  statusToastMessageElement.innerHTML = statusValues[type].message;
  const toast = new bootstrap.Toast(statusToast);
  toast.show();
};

const clearValidationErrors = (fields) => {
  fields.forEach((field) => {
    field.classList.remove("is-invalid");
    const feedback = field
      .closest(".mb-3, .form-check")
      ?.querySelector(".invalid-feedback");
    if (feedback !== null && feedback !== void 0) {
      feedback.innerHTML = "";
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
    const feedback = field
      .closest(".mb-3, .form-check")
      ?.querySelector(".invalid-feedback");
    if (feedback !== null && feedback !== void 0) {
      feedback.innerHTML = Array.isArray(messages)
        ? messages.join(" ")
        : String(messages);
    }
  });
};

document.querySelectorAll(editButtonSelector).forEach((button) => {
  button.addEventListener("click", (event) => {
    event.preventDefault();
    toggleEditField(button, true);
  });
});

const toggleEditField = (button, state = true) => {
  const targetElementName = button.dataset.ieFor;
  if (targetElementName === void 0) {
    return;
  }
  const activateButton = document.querySelector(
    `[data-academic-persons-inline-edit-activate-btn][data-ie-for="${targetElementName}"]`,
  );
  const inputElement = document.querySelector(`[id="${targetElementName}"]`);
  if (
    !(
      inputElement instanceof HTMLInputElement ||
      inputElement instanceof HTMLSelectElement ||
      inputElement instanceof HTMLTextAreaElement
    )
  ) {
    return;
  }
  if (state) {
    inputElement.classList.remove("d-none");
    activateButton.classList.add("d-none");
    document
      .querySelectorAll(`[data-ie-dismiss][data-ie-for="${targetElementName}"]`)
      .forEach((dismissButton) => {
        dismissButton.classList.remove("d-none");
      });
    document
      .querySelectorAll(`[data-ie-save][data-ie-for="${targetElementName}"]`)
      .forEach((saveButton) => {
        saveButton.classList.remove("d-none");
      });
  } else {
    inputElement.classList.add("d-none");
    activateButton.classList.remove("d-none");
    document
      .querySelectorAll(`[data-ie-dismiss][data-ie-for="${targetElementName}"]`)
      .forEach((dismissButton) => {
        dismissButton.classList.add("d-none");
      });
    document
      .querySelectorAll(`[data-ie-save][data-ie-for="${targetElementName}"]`)
      .forEach((saveButton) => {
        saveButton.classList.add("d-none");
      });
  }
};

document.querySelectorAll(`[data-ie-dismiss]`).forEach((button) => {
  button.addEventListener("click", (event) => {
    toggleEditField(button, false);
  });
});

document.querySelectorAll(`[data-ie-save]`).forEach((button) => {
  button.addEventListener("click", (event) => {
    toggleEditField(button, false);
  });
});

document.querySelectorAll(formSelector).forEach((form) => {
  const fields = Array.from(form.querySelectorAll(fieldSelector)).filter(
    (field) =>
      field instanceof HTMLInputElement ||
      field instanceof HTMLSelectElement ||
      field instanceof HTMLTextAreaElement,
  );
  const initialValues = new Map(
    fields.map((field) => [field.name, getFieldValue(field)]),
  );

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
      showStatus("info");
      return;
    }

    const profileUid = Number.parseInt(form.dataset.profileUid ?? "", 10);
    const updateUrl = form.dataset.updateUrl;
    if (
      !Number.isInteger(profileUid) ||
      profileUid <= 0 ||
      updateUrl === void 0
    ) {
      showStatus("danger");
      return;
    }

    form.setAttribute("aria-busy", "true");
    showStatus("info");

    try {
      const response = await fetch(updateUrl, {
        method: "POST",
        credentials: "same-origin",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          profile: profileUid,
          data,
        }),
      });
      const result = await response.json().catch(() => null);

      if (!response.ok || result?.success !== true) {
        if (result?.errors !== void 0 && typeof result.errors === "object") {
          showValidationErrors(fields, result.errors);
          showStatus("danger");
        } else {
          showStatus(
            result?.message ?? form.dataset?.messageErrorMessage ?? "",
            "danger",
          );
        }
        return;
      }

      Object.keys(data).forEach((propertyName) => {
        initialValues.set(propertyName, data[propertyName]);
      });
      showStatus("success");
      updateView(initialValues, fields);
    } catch {
      showStatus("danger");
    } finally {
      form.removeAttribute("aria-busy");
    }
  });
});

// Map initialValues, Array fields
const updateView = (initialValues, fields) => {
  fields.forEach((field) => {
    if (!field.name) {
      return;
    }
    const initialValue = initialValues.get(field.name) ?? "";
    switch (true) {
      case field instanceof HTMLSelectElement:
        field.value = initialValue;
        break;

      case field instanceof HTMLInputElement:
      case field instanceof HTMLTextAreaElement:
        field.value = initialValue;
        //check which state applies and render different buttons
        checkButtonTemplateType(initialValue);
        break;

      default:
        break;
    }
  });
};

const checkButtonTemplateType = (fieldValue) => {
  console.log(fieldValue);
//  console.log(templateInlineEditBtn);
//  console.log(templateInlineEditNew);
};
