# Red pública mínima: VCN, internet gateway y una subnet con firewall que solo
# abre SSH (desde admin_cidr) y HTTP/HTTPS (para Caddy y Let's Encrypt).

locals {
  compartment_id = var.compartment_ocid != "" ? var.compartment_ocid : var.tenancy_ocid
  name           = "agendapro"
}

resource "oci_core_vcn" "main" {
  compartment_id = local.compartment_id
  display_name   = "${local.name}-vcn"
  cidr_blocks    = ["10.0.0.0/16"]
  dns_label      = local.name
}

resource "oci_core_internet_gateway" "main" {
  compartment_id = local.compartment_id
  vcn_id         = oci_core_vcn.main.id
  display_name   = "${local.name}-igw"
  enabled        = true
}

resource "oci_core_route_table" "public" {
  compartment_id = local.compartment_id
  vcn_id         = oci_core_vcn.main.id
  display_name   = "${local.name}-public-rt"

  route_rules {
    destination       = "0.0.0.0/0"
    destination_type  = "CIDR_BLOCK"
    network_entity_id = oci_core_internet_gateway.main.id
  }
}

resource "oci_core_security_list" "public" {
  compartment_id = local.compartment_id
  vcn_id         = oci_core_vcn.main.id
  display_name   = "${local.name}-public-sl"

  egress_security_rules {
    destination = "0.0.0.0/0"
    protocol    = "all"
  }

  ingress_security_rules {
    description = "SSH solo desde la IP del administrador"
    source      = var.admin_cidr
    protocol    = "6" # TCP

    tcp_options {
      min = 22
      max = 22
    }
  }

  dynamic "ingress_security_rules" {
    for_each = {
      "HTTP (Let's Encrypt y redirección a HTTPS)" = { protocol = "6", port = 80 }
      "HTTPS"                                      = { protocol = "6", port = 443 }
      "HTTP/3 (QUIC)"                              = { protocol = "17", port = 443 }
    }

    content {
      description = ingress_security_rules.key
      source      = "0.0.0.0/0"
      protocol    = ingress_security_rules.value.protocol

      dynamic "tcp_options" {
        for_each = ingress_security_rules.value.protocol == "6" ? [1] : []
        content {
          min = ingress_security_rules.value.port
          max = ingress_security_rules.value.port
        }
      }

      dynamic "udp_options" {
        for_each = ingress_security_rules.value.protocol == "17" ? [1] : []
        content {
          min = ingress_security_rules.value.port
          max = ingress_security_rules.value.port
        }
      }
    }
  }

  # Path MTU discovery: sin esto, algunas conexiones TLS se cuelgan.
  ingress_security_rules {
    description = "ICMP fragmentation needed"
    source      = "0.0.0.0/0"
    protocol    = "1"

    icmp_options {
      type = 3
      code = 4
    }
  }
}

resource "oci_core_subnet" "public" {
  compartment_id    = local.compartment_id
  vcn_id            = oci_core_vcn.main.id
  display_name      = "${local.name}-public-subnet"
  cidr_block        = "10.0.1.0/24"
  dns_label         = "public"
  route_table_id    = oci_core_route_table.public.id
  security_list_ids = [oci_core_security_list.public.id]
}
