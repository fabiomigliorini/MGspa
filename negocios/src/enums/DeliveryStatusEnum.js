const PENDING = {
  name: "Entrega Pendente",
  value: 1,
  color: "yellow",
  textColor: "black",
  icon: "mdi-clock-outline",
};

const IN_TRANSIT = {
  name: "Em Transporte",
  value: 2,
  color: "blue",
  textColor: "white",
  icon: "mdi-truck-fast-outline",
};

const DELIVERED = {
  name: "Entregue",
  value: 3,
  color: "green",
  textColor: "white",
  icon: "mdi-truck-check-outline",
};

const CANCELLED = {
  name: "Cancelado",
  value: 4,
  color: "red",
  textColor: "white",
  icon: "mdi-truck-alert-outline",
};

const DeliveryStatusEnum = {
  PENDING,
  1: PENDING,
  IN_TRANSIT,
  2: IN_TRANSIT,
  DELIVERED,
  3: DELIVERED,
  CANCELLED,
  4: CANCELLED,
};

export { DeliveryStatusEnum };
