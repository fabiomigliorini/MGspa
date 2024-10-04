import { api } from "src/boot/axios";
import { DeliveryStatusEnum } from "src/enums/DeliveryStatusEnum";
import { negocioStore } from "src/stores/negocio";

class DeliveryModel {
  static async request(formData) {
    const { data } = await api.post("/api/v1/delivery/request", formData);

    const store = negocioStore();

    if (store.negocio) {
      store.negocio.deliveries.push(data);
    }

    return data;
  }

  static async cancel(deliveryId) {
    await api.post(`/api/v1/delivery/cancel/${deliveryId}`);

    const store = negocioStore();

    if (store.negocio) {
      const delivery = store.negocio.deliveries.find(
        (delivery) => delivery.id == deliveryId
      );

      if (!delivery) return;

      delivery.status = DeliveryStatusEnum.CANCELLED.value;
    }

    return data;
  }
}

export { DeliveryModel };
