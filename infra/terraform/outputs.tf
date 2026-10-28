output "public_ip" {
  description = "IP pública del servidor: apunta aquí el registro A de tu dominio."
  value       = oci_core_instance.app.public_ip
}

output "ssh_command" {
  description = "Comando para entrar al servidor."
  value       = "ssh ubuntu@${oci_core_instance.app.public_ip}"
}

output "image" {
  description = "Imagen de Ubuntu usada al crear la VM."
  value       = data.oci_core_images.ubuntu_arm.images[0].display_name
}
