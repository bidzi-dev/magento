# Bidzi para Magento 2

![Magento](https://img.shields.io/badge/Magento-%3E%3D2.4.0-orange) ![PHP](https://img.shields.io/badge/PHP-%3E%3D7.3-blue) ![License](https://img.shields.io/badge/License-OSL--3.0%20%2F%20AFL--3.0-lightgrey)

Extensión oficial de Bidzi para Magento 2. Permite a los comercios aceptar pagos con tarjeta de crédito y débito a través de la pasarela de pagos de Bidzi.

---

## Características

- Pagos con tarjeta de crédito y débito (Visa, Mastercard, American Express, Carnet)
- Autenticación 3D Secure (redirección y modal)
- Autorización y captura automática
- Reembolsos totales y parciales
- Entornos sandbox y producción
- Soporte para México
- Tokenización segura de tarjetas
- Prevención de fraude mediante seguimiento de sesión de dispositivo

## Requisitos

- PHP >= 7.3.0
- Magento >= 2.4.0
- GuzzleHTTP ^7.0 (se instala automáticamente con Composer)

## Instalación

Ir a la carpeta raíz del proyecto de Magento y seguir los siguiente pasos:

```bash
composer require bidzi/magento
php bin/magento module:enable Bidzi_Cards --clear-static-content
php bin/magento setup:upgrade
php bin/magento cache:clean
```

## Actualización

En caso de ya contar con el módulo instalado y sea necesario actualizar, seguir los siguientes pasos:

```bash
composer clear-cache
composer update bidzi/magento
bin/magento setup:upgrade
php bin/magento cache:clean
```

## Configuración

1. En el panel de Magento, ve a **Tiendas > Configuración > Ventas > Métodos de pago > Bidzi**
2. Ingresa las credenciales de tu cuenta Bidzi:

| Campo                  | Descripción                                                     |
| ---------------------- | --------------------------------------------------------------- |
| Merchant ID            | Identificador único de tu comercio                              |
| Client ID              | ID de cliente para autenticación OAuth                          |
| Client Secret          | Secreto de cliente (campo encriptado)                           |
| Public Key             | Llave pública para inicializar el widget de pago                |
| Webhook Signing Secret | Secreto para validar notificaciones de Bidzi (campo encriptado) |
| Modo Sandbox           | Activa el entorno de pruebas                                    |

Para obtener tus credenciales, consulta la [documentación oficial](https://docs.bidzi.com/docs/magento).

## Métodos de pago soportados

| Marca            | Código |
| ---------------- | ------ |
| Visa             | VI     |
| Mastercard       | MC     |
| American Express | AE     |
| Carnet           | CN     |

## Países y monedas soportados

| País        | Monedas  |
| ----------- | -------- |
| México (MX) | MXN, USD |

## Soporte

- Documentación: [docs.bidzi.com/docs/magento](https://docs.bidzi.com/docs/magento)
- Correo: [support@bidzi.mx](mailto:support@bidzi.mx)

## Licencia

OSL-3.0 / AFL-3.0 — consulta los archivos [LICENSE](LICENSE) y [LICENSE_AFL.txt](LICENSE_AFL.txt) para más detalles.
