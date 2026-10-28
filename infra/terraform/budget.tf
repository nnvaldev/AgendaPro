# Red de seguridad: si algo llegara a generar costo, llega un email. En Always
# Free el gasto esperado es 0, así que cualquier consumo dispara la alerta.

resource "oci_budget_budget" "monthly" {
  compartment_id = var.tenancy_ocid
  display_name   = "${local.name}-presupuesto"
  description    = "Alerta ante cualquier gasto: la cuenta debe costar 0."
  amount         = var.budget_amount_usd
  reset_period   = "MONTHLY"
  target_type    = "COMPARTMENT"
  targets        = [local.compartment_id]
}

resource "oci_budget_alert_rule" "any_spend" {
  budget_id      = oci_budget_budget.monthly.id
  display_name   = "${local.name}-alerta-gasto"
  type           = "ACTUAL"
  threshold      = 1
  threshold_type = "PERCENTAGE"
  recipients     = var.budget_alert_email
  message        = "AgendaPro: la cuenta de Oracle Cloud empezó a generar costo. Revisa los recursos fuera de Always Free."
}
