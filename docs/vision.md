### Dónde puedes probar los modelos

- **OpenAI Playground (en la plataforma):** desde cualquier página de modelo puedes usar el botón **“Try in Playground”** para probar prompts y adjuntar imágenes. Por ejemplo, en **GPT‑5 nano** aparece ese acceso directo. [https://platform.openai.com/docs/models/gpt-5-nano/](https://platform.openai.com/docs/models/gpt-5-nano/)
- **En la API (Responses API):** si ya estás integrando, puedes probar rápido con una llamada a `POST /v1/responses`. [https://platform.openai.com/docs/api-reference/responses](https://platform.openai.com/docs/api-reference/responses)

**Resumen de razonamiento:** Para “probar”, lo más directo es Playground o una llamada mínima a Responses. Para “más económico”, hay que mirar precios oficiales por modelo que además soporte **input de imagen**.

---

### ¿Qué modelo es el más económico (sin dejar de ser efectivo) para capturas de pago?

Para extraer **fecha, monto, referencia y descripción** desde una **imagen** (OCR + comprensión), el más barato de la familia GPT‑5 que acepta **imagen como input** es:

- **GPT‑5 nano** → **$0.05 / 1M tokens de entrada** y **$0.40 / 1M tokens de salida**, y soporta **Text+Image input**. [https://platform.openai.com/docs/models/gpt-5-nano/](https://platform.openai.com/docs/models/gpt-5-nano/)

Si notas que en algunos comprobantes la precisión baja (texto borroso, fondos, formatos raros), el siguiente “sweet spot” costo/calidad suele ser:

- **GPT‑5 mini** → **$0.25 / 1M input** y **$2.00 / 1M output**, también con **imagen de entrada**. [https://platform.openai.com/docs/models/gpt-5-mini/](https://platform.openai.com/docs/models/gpt-5-mini/)

**Resumen de razonamiento:** Para tu caso el costo depende mucho de tokens, pero el ranking de “más barato” oficial para modelos con visión queda claro en las páginas de GPT‑5 nano y GPT‑5 mini.

---

### Cómo extraer esos 4 campos desde una imagen (recomendado: JSON con esquema)

1. Envía la imagen como `input_image` (URL o `data:image/...;base64,...`). [https://platform.openai.com/docs/guides/images-vision](https://platform.openai.com/docs/guides/images-vision)
2. Fuerza una salida estable con **Structured Outputs** (JSON Schema) para que siempre vuelvan las mismas claves. [https://platform.openai.com/docs/guides/structured-outputs](https://platform.openai.com/docs/guides/structured-outputs)

#### Python (Responses API + JSON Schema)

```python
import base64
from openai import OpenAI

client = OpenAI()

def to_b64_data_url(path: str) -> str:
    b64 = base64.b64encode(open(path, "rb").read()).decode("utf-8")
    return f"data:image/jpeg;base64,{b64}"

schema = {
  "name": "payment_receipt",
  "schema": {
    "type": "object",
    "additionalProperties": False,
    "properties": {
      "fecha": {"type": ["string", "null"], "description": "Fecha del pago (idealmente ISO-8601)"},
      "monto": {"type": ["number", "null"], "description": "Monto numérico"},
      "referencia": {"type": ["string", "null"]},
      "descripcion": {"type": ["string", "null"]}
    },
    "required": ["fecha", "monto", "referencia", "descripcion"]
  },
  "strict": True
}

image_url = to_b64_data_url("captura_pago.jpg")

resp = client.responses.create(
    model="gpt-5-nano",
    input=[{
        "role": "user",
        "content": [
            {"type": "input_text",
             "text": "Extrae SOLO: fecha, monto, referencia y descripción del comprobante. "
                     "Si un campo no aparece, devuélvelo como null."},
            {"type": "input_image", "image_url": image_url},
        ],
    }],
    text={"format": {"type": "json_schema", **schema}},
)

print(resp.output_text)
```

**Notas rápidas útiles**

- Si tu “monto” a veces viene con moneda (ej. “$1,234.50”), puedes pedir además un campo `moneda` o indicar reglas (decimal con punto, etc.).
- No pegues claves API en el frontend; pon tu `OPENAI_API_KEY` en variables de entorno y usa un backend.

**Resumen de razonamiento:** Para extracción “tipo recibo”, lo que más reduce fallos es (a) enviar bien la imagen y (b) obligar JSON con esquema; así tu app no depende de “parsear texto libre”.

---

Si me dices **(1)** si las capturas son de **banco / pasarela / POS**, y **(2)** el idioma/país de los comprobantes, te ajusto el esquema (por ejemplo, normalizar fecha a ISO y manejar separadores decimales).
