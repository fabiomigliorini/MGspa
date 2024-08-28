import { api } from "src/boot/axios";
import { ref } from "vue";

const REQUEST_DELIVERY_PATH = "";

const statusEnum = {
  1: {
    name: "Pendente",
    color: "yellow",
    textColor: "black",
    icon: "mdi-clock-outline",
  },
  2: {
    name: "Em Transporte",
    color: "blue",
    textColor: "white",
    icon: "mdi-truck-fast-outline",
  },
  3: {
    name: "Entregue",
    color: "green",
    textColor: "white",
    icon: "mdi-truck-check-outline",
  },
  4: {
    name: "Cancelado",
    color: "red",
    textColor: "white",
    icon: "mdi-truck-alert-outline",
  },
};

function useRequestDelivery(formData) {
  const status = ref(null);

  const eventTarget = new EventTarget();

  let changeToDoneTimeout = null;
  let changeToProgressTimeout = null;

  const request = async () => {
    // const { data } = await api.post(REQUEST_DELIVERY_PATH, formData);

    await new Promise((resolve) => {
      setTimeout(resolve, 3000);
    });

    status.value = statusEnum[1];

    eventTarget.addEventListener("change", (event) => {
      status.value = statusEnum[event.detail.status];
    });

    changeToProgressTimeout = setTimeout(() => {
      eventTarget.dispatchEvent(
        new CustomEvent("change", {
          detail: { status: 2 },
        })
      );
    }, 5000);

    changeToDoneTimeout = setTimeout(() => {
      eventTarget.dispatchEvent(
        new CustomEvent("change", {
          detail: { status: 3 },
        })
      );
    }, 10000);

    return data;
  };

  const cancel = async () => {
    clearTimeout(changeToProgressTimeout);
    clearTimeout(changeToDoneTimeout);

    await new Promise((resolve) => {
      setTimeout(resolve, 3000);
    });

    eventTarget.dispatchEvent(
      new CustomEvent("change", {
        detail: { status: 4 },
      })
    );
  };

  return {
    request,
    status,
    cancel,
  };
}

export { useRequestDelivery };
