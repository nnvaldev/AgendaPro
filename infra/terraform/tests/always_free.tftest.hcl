# Tests sin cuenta de Oracle: el provider se simula, así que `terraform test`
# corre un plan completo en local o en CI y verifica las reglas que importan.

mock_provider "oci" {
  mock_data "oci_identity_availability_domains" {
    defaults = {
      availability_domains = [{ name = "AD-1" }, { name = "AD-2" }, { name = "AD-3" }]
    }
  }

  mock_data "oci_core_images" {
    defaults = {
      images = [{ id = "ocid1.image.oc1..ubuntu", display_name = "Canonical-Ubuntu-24.04-aarch64" }]
    }
  }
}

variables {
  tenancy_ocid       = "ocid1.tenancy.oc1..test"
  user_ocid          = "ocid1.user.oc1..test"
  fingerprint        = "00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00"
  private_key_path   = "/dev/null"
  ssh_public_key     = "ssh-ed25519 AAAATEST test@example.com"
  admin_cidr         = "200.87.10.4/32"
  budget_alert_email = "test@example.com"
}

run "vm_dentro_de_always_free" {
  command = plan

  assert {
    condition     = oci_core_instance.app.shape == "VM.Standard.A1.Flex"
    error_message = "La VM debe ser Ampere A1, la única forma ARM incluida en Always Free."
  }

  assert {
    condition     = oci_core_instance.app.shape_config[0].ocpus <= 4 && oci_core_instance.app.shape_config[0].memory_in_gbs <= 24
    error_message = "La VM supera el límite gratuito de 4 OCPU / 24 GB."
  }

  assert {
    condition     = oci_core_instance.app.source_details[0].boot_volume_size_in_gbs <= 200
    error_message = "El disco supera los 200 GB gratuitos."
  }

  assert {
    condition     = oci_core_instance.app.availability_domain == "AD-1"
    error_message = "Por defecto debe usarse el primer dominio de disponibilidad."
  }
}

run "ssh_restringido_y_web_abierta" {
  command = plan

  assert {
    condition = [
      for rule in oci_core_security_list.public.ingress_security_rules :
      rule.source if try(rule.tcp_options[0].min, null) == 22
    ] == [var.admin_cidr]
    error_message = "Debe haber exactamente una regla de SSH y solo para admin_cidr."
  }

  assert {
    condition = length([
      for rule in oci_core_security_list.public.ingress_security_rules :
      rule if rule.source == "0.0.0.0/0" && contains([80, 443], try(rule.tcp_options[0].min, -1))
    ]) == 2
    error_message = "HTTP y HTTPS deben estar abiertos a internet para Caddy y Let's Encrypt."
  }
}

run "presupuesto_alerta_ante_cualquier_gasto" {
  command = plan

  assert {
    condition     = oci_budget_alert_rule.any_spend.threshold == 1 && oci_budget_alert_rule.any_spend.type == "ACTUAL"
    error_message = "La alerta debe saltar con el primer gasto real."
  }
}

run "rechaza_recursos_pagos" {
  command = plan

  variables {
    instance_ocpus     = 8
    instance_memory_gb = 48
  }

  expect_failures = [var.instance_ocpus, var.instance_memory_gb]
}
