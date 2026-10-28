# --- Credenciales de la API de OCI ------------------------------------------

variable "tenancy_ocid" {
  description = "OCID del tenancy (Perfil → Tenancy)."
  type        = string
}

variable "user_ocid" {
  description = "OCID del usuario que firma las llamadas a la API."
  type        = string
}

variable "fingerprint" {
  description = "Fingerprint de la API key subida al usuario."
  type        = string
}

variable "private_key_path" {
  description = "Ruta a la clave privada (.pem) de la API key."
  type        = string
}

variable "region" {
  description = "Región home de la cuenta. Las más cercanas a Bolivia: sa-saopaulo-1, sa-santiago-1."
  type        = string
  default     = "sa-saopaulo-1"
}

variable "compartment_ocid" {
  description = "Compartimento donde crear los recursos. Vacío = el tenancy raíz."
  type        = string
  default     = ""
}

# --- Servidor ---------------------------------------------------------------

variable "ssh_public_key" {
  description = "Clave pública SSH para el usuario ubuntu (contenido, no la ruta)."
  type        = string
}

variable "admin_cidr" {
  description = "IP o rango que puede entrar por SSH, p. ej. \"200.87.10.4/32\". Usa 0.0.0.0/0 solo si tu IP cambia seguido."
  type        = string
}

variable "availability_domain_index" {
  description = "Dominio de disponibilidad (0, 1 o 2). Si la VM ARM da 'Out of capacity', prueba con otro."
  type        = number
  default     = 0
}

# Always Free: hasta 4 OCPU y 24 GB de RAM en total para VMs Ampere A1 y
# 200 GB de almacenamiento en bloque. Por defecto se usa la mitad, que suele
# conseguirse más fácil cuando hay poca capacidad en la región.
variable "instance_ocpus" {
  description = "OCPUs de la VM Ampere A1 (máximo gratis: 4)."
  type        = number
  default     = 2

  validation {
    condition     = var.instance_ocpus >= 1 && var.instance_ocpus <= 4
    error_message = "Para mantenerse en Always Free, usa entre 1 y 4 OCPUs."
  }
}

variable "instance_memory_gb" {
  description = "RAM de la VM en GB (máximo gratis: 24)."
  type        = number
  default     = 12

  validation {
    condition     = var.instance_memory_gb >= 1 && var.instance_memory_gb <= 24
    error_message = "Para mantenerse en Always Free, usa entre 1 y 24 GB."
  }
}

variable "boot_volume_gb" {
  description = "Disco de arranque en GB (Always Free: 200 GB en total)."
  type        = number
  default     = 50

  validation {
    condition     = var.boot_volume_gb >= 50 && var.boot_volume_gb <= 200
    error_message = "El disco debe tener entre 50 GB (mínimo de OCI) y 200 GB (límite gratuito)."
  }
}

# --- Presupuesto ------------------------------------------------------------

variable "budget_alert_email" {
  description = "Email que recibe la alerta si la cuenta llega a generar algún costo."
  type        = string
}

variable "budget_amount_usd" {
  description = "Monto mensual del presupuesto. La alerta salta al llegar a cualquier gasto."
  type        = number
  default     = 1
}
