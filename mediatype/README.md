# Media Type — LIGMEE Ligações

Este é o webhook usado para disparar ligações via [LIGMEE](https://portal.ligmee.com) a partir do Zabbix.

## Como criar no Zabbix

Acesse **Administration → Media types → Create media type** com as seguintes configurações:

| Campo | Valor |
|---|---|
| Name | LIGMEE LIGAÇÕES (coloque uma descrição sua) |
| Type | Webhook |
| Script | conteúdo do arquivo `ligmee-webhook.js` |
| Timeout | 30s |
| Process tags | desmarcado |
| Include event menu entry | desmarcado |

## Parâmetros obrigatórios

Adicione estes parâmetros na aba **Parameters**:

| Nome | Valor |
|---|---|
| `api_key` | sua chave de API do LIGMEE |
| `branch_number` | ramal de origem (máx 4 dígitos) |
| `destination_number` | número de destino — **este é o campo atualizado pelo módulo Plantão** |
| `voice_type` | `Feminina` ou `Masculina` |
| `alert_message` | `{ALERT.MESSAGE}` |
| `alert_subject` | `{ALERT.SUBJECT}` |
| `event_id` | `{EVENT.ID}` |
| `event_nseverity` | `{EVENT.NSEVERITY}` |
| `event_recovery_value` | `{EVENT.RECOVERY.VALUE}` |
| `event_source` | `{EVENT.SOURCE}` |
| `event_update_status` | `{EVENT.UPDATE.STATUS}` |
| `event_value` | `{EVENT.VALUE}` |
| `trigger_id` | `{TRIGGER.ID}` |
| `zabbix_url` | URL da sua instância Zabbix |

## Integração com o módulo Plantão

O módulo Plantão atualiza automaticamente o campo `destination_number` nos media types configurados quando uma escala é salva. Você precisa criar dois media types usando este mesmo webhook:

- **Ligações principal** — para o técnico de plantão
- **Ligações reserva** — para o técnico reserva

Depois, coloque os IDs desses media types nos arquivos `PlantaoSave.php` e `PlantaoApply.php` conforme descrito no README principal.

## Onde obter a API Key

Acesse **portal.ligmee.com** → configurações da conta → API Keys.
