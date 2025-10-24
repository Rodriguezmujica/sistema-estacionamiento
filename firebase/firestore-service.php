<?php
/**
 * 🔥 SERVICIO DE FIRESTORE
 * Sistema de Estacionamiento Los Ríos
 * 
 * Este archivo maneja todas las operaciones con Firestore
 */

require_once __DIR__ . '/firebase-config.php';

class FirestoreService {
    private $projectId;
    private $baseUrl;
    
    public function __construct() {
        $this->projectId = FIREBASE_PROJECT_ID;
        $this->baseUrl = FIRESTORE_URL;
    }
    
    /**
     * Crear documento en Firestore
     */
    public function createDocument($collection, $documentId, $data) {
        $url = $this->baseUrl . '/' . $collection . '/' . $documentId;
        
        $firestoreData = [
            'fields' => $this->convertToFirestoreFormat($data)
        ];
        
        return $this->makeRequest($url, 'PATCH', $firestoreData);
    }
    
    /**
     * Obtener documento de Firestore
     */
    public function getDocument($collection, $documentId) {
        $url = $this->baseUrl . '/' . $collection . '/' . $documentId;
        return $this->makeRequest($url, 'GET');
    }
    
    /**
     * Actualizar documento en Firestore
     */
    public function updateDocument($collection, $documentId, $data) {
        $url = $this->baseUrl . '/' . $collection . '/' . $documentId;
        
        $firestoreData = [
            'fields' => $this->convertToFirestoreFormat($data)
        ];
        
        return $this->makeRequest($url, 'PATCH', $firestoreData);
    }
    
    /**
     * Eliminar documento de Firestore
     */
    public function deleteDocument($collection, $documentId) {
        $url = $this->baseUrl . '/' . $collection . '/' . $documentId;
        return $this->makeRequest($url, 'DELETE');
    }
    
    /**
     * Listar documentos de una colección
     */
    public function listDocuments($collection, $limit = 50, $pageToken = null) {
        $url = $this->baseUrl . '/' . $collection;
        
        $params = ['pageSize' => $limit];
        if ($pageToken) {
            $params['pageToken'] = $pageToken;
        }
        
        $url .= '?' . http_build_query($params);
        
        return $this->makeRequest($url, 'GET');
    }
    
    /**
     * Buscar documentos con filtros
     */
    public function queryDocuments($collection, $filters = [], $orderBy = null, $limit = 50) {
        $url = $this->baseUrl . ':runQuery';
        
        $query = [
            'structuredQuery' => [
                'from' => [['collectionId' => $collection]]
            ]
        ];
        
        // Agregar filtros
        if (!empty($filters)) {
            $query['structuredQuery']['where'] = [
                'compositeFilter' => [
                    'op' => 'AND',
                    'filters' => $filters
                ]
            ];
        }
        
        // Agregar ordenamiento
        if ($orderBy) {
            $query['structuredQuery']['orderBy'] = $orderBy;
        }
        
        // Agregar límite
        $query['structuredQuery']['limit'] = $limit;
        
        return $this->makeRequest($url, 'POST', $query);
    }
    
    /**
     * Convertir datos PHP a formato Firestore
     */
    private function convertToFirestoreFormat($data) {
        $firestoreData = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $firestoreData[$key] = ['stringValue' => $value];
            } elseif (is_int($value)) {
                $firestoreData[$key] = ['integerValue' => $value];
            } elseif (is_float($value)) {
                $firestoreData[$key] = ['doubleValue' => $value];
            } elseif (is_bool($value)) {
                $firestoreData[$key] = ['booleanValue' => $value];
            } elseif (is_array($value)) {
                $firestoreData[$key] = ['arrayValue' => [
                    'values' => array_map([$this, 'convertToFirestoreFormat'], $value)
                ]];
            } elseif ($value instanceof DateTime) {
                $firestoreData[$key] = ['timestampValue' => $value->format('c')];
            } elseif (is_null($value)) {
                $firestoreData[$key] = ['nullValue' => null];
            } else {
                $firestoreData[$key] = ['stringValue' => (string)$value];
            }
        }
        
        return $firestoreData;
    }
    
    /**
     * Convertir datos Firestore a formato PHP
     */
    public function convertFromFirestoreFormat($firestoreData) {
        if (!isset($firestoreData['fields'])) {
            return null;
        }
        
        $data = [];
        
        foreach ($firestoreData['fields'] as $key => $value) {
            if (isset($value['stringValue'])) {
                $data[$key] = $value['stringValue'];
            } elseif (isset($value['integerValue'])) {
                $data[$key] = (int)$value['integerValue'];
            } elseif (isset($value['doubleValue'])) {
                $data[$key] = (float)$value['doubleValue'];
            } elseif (isset($value['booleanValue'])) {
                $data[$key] = $value['booleanValue'];
            } elseif (isset($value['arrayValue'])) {
                $data[$key] = array_map([$this, 'convertFromFirestoreFormat'], $value['arrayValue']['values']);
            } elseif (isset($value['timestampValue'])) {
                $data[$key] = new DateTime($value['timestampValue']);
            } elseif (isset($value['nullValue'])) {
                $data[$key] = null;
            }
        }
        
        return $data;
    }
    
    /**
     * Hacer petición HTTP a Firestore
     */
    private function makeRequest($url, $method = 'GET', $data = null) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . getFirebaseAccessToken()
        ]);
        
        if ($method === 'POST' || $method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => $error,
                'status' => 0
            ];
        }
        
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'data' => json_decode($response, true),
            'status' => $httpCode,
            'raw_response' => $response
        ];
    }
    
    /**
     * Crear filtro para consultas
     */
    public function createFilter($field, $operator, $value) {
        $operators = [
            '=' => 'EQUAL',
            '!=' => 'NOT_EQUAL',
            '<' => 'LESS_THAN',
            '<=' => 'LESS_THAN_OR_EQUAL',
            '>' => 'GREATER_THAN',
            '>=' => 'GREATER_THAN_OR_EQUAL',
            'in' => 'IN',
            'not_in' => 'NOT_IN',
            'array_contains' => 'ARRAY_CONTAINS'
        ];
        
        if (!isset($operators[$operator])) {
            throw new Exception("Operador no válido: $operator");
        }
        
        $filter = [
            'fieldFilter' => [
                'field' => ['fieldPath' => $field],
                'op' => $operators[$operator],
                'value' => $this->convertToFirestoreFormat([$value])[$value]
            ]
        ];
        
        return $filter;
    }
    
    /**
     * Crear ordenamiento para consultas
     */
    public function createOrderBy($field, $direction = 'ASC') {
        return [
            'field' => ['fieldPath' => $field],
            'direction' => strtoupper($direction) === 'DESC' ? 'DESCENDING' : 'ASCENDING'
        ];
    }
}

// Funciones de conveniencia para uso global
function getFirestoreService() {
    return new FirestoreService();
}

// Funciones específicas para el sistema de estacionamiento
function createTicket($patente, $tipoServicio, $usuarioId, $clienteNombre = '') {
    $firestore = getFirestoreService();
    
    $ticketData = [
        'patente' => $patente,
        'tipo_servicio' => $tipoServicio,
        'fecha_ingreso' => new DateTime(),
        'fecha_salida' => null,
        'precio' => 0.0,
        'pagado' => false,
        'usuario_id' => $usuarioId,
        'cliente_nombre' => $clienteNombre
    ];
    
    $documentId = 'ticket_' . time() . '_' . uniqid();
    return $firestore->createDocument('tickets', $documentId, $ticketData);
}

function getTicket($ticketId) {
    $firestore = getFirestoreService();
    return $firestore->getDocument('tickets', $ticketId);
}

function updateTicket($ticketId, $data) {
    $firestore = getFirestoreService();
    return $firestore->updateDocument('tickets', $ticketId, $data);
}

function listTickets($limit = 50) {
    $firestore = getFirestoreService();
    return $firestore->listDocuments('tickets', $limit);
}

function searchTicketsByPatente($patente) {
    $firestore = getFirestoreService();
    $filters = [$firestore->createFilter('patente', '=', $patente)];
    return $firestore->queryDocuments('tickets', $filters);
}

function createServicioLavado($patente, $tipoLavado, $precioBase, $usuarioId, $clienteNombre = '') {
    $firestore = getFirestoreService();
    
    $servicioData = [
        'patente' => $patente,
        'tipo_lavado' => $tipoLavado,
        'precio_base' => $precioBase,
        'precio_extra' => 0.0,
        'motivos_extra' => [],
        'fecha_servicio' => new DateTime(),
        'usuario_id' => $usuarioId,
        'cliente_nombre' => $clienteNombre
    ];
    
    $documentId = 'servicio_' . time() . '_' . uniqid();
    return $firestore->createDocument('servicios_lavado', $documentId, $servicioData);
}

function getServicioLavado($servicioId) {
    $firestore = getFirestoreService();
    return $firestore->getDocument('servicios_lavado', $servicioId);
}

function updateServicioLavado($servicioId, $data) {
    $firestore = getFirestoreService();
    return $firestore->updateDocument('servicios_lavado', $servicioId, $data);
}

function listServiciosLavado($limit = 50) {
    $firestore = getFirestoreService();
    return $firestore->listDocuments('servicios_lavado', $limit);
}
?>
