<template>
  <div class="p-6 max-w-7xl mx-auto space-y-6">
    <!-- Cabeçalho da Importação -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between bg-white p-6 rounded-lg shadow-sm border border-gray-200">
      <div>
        <h1 class="text-xl font-bold text-gray-900">Tratamento de Importação</h1>
        <p class="text-sm text-gray-500 mt-1">
          Arquivo: <span class="font-semibold text-gray-700">{{ importData.original_filename }}</span> |
          Status: <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">{{ importData.status_label }}</span>
        </p>
      </div>
      <div class="mt-4 md:mt-0 flex gap-3">
        <!-- Botão Lançar se estiver pronto -->
        <button
          v-if="importData.can_launch"
          @click="launchImport"
          class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-md text-sm shadow-sm transition"
        >
          Lançar Registros
        </button>
        <!-- Botão Reverter se já lançado -->
        <button
          v-if="importData.can_revert"
          @click="revertImport"
          class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-medium rounded-md text-sm shadow-sm transition"
        >
          Reverter Lançamento
        </button>
      </div>
    </div>

    <!-- Barra de Filtros Persistentes -->
    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 flex flex-col md:flex-row gap-4 items-center justify-between">
      <div class="flex flex-wrap gap-4 items-center w-full md:w-auto">
        <!-- Filtro por Nome/Matrícula do Funcionário -->
        <div class="w-full md:w-64">
          <input
            type="text"
            v-model="filters.employee"
            placeholder="Filtrar por funcionário..."
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
          />
        </div>

        <!-- Switch Apenas Inconsistentes -->
        <label class="inline-flex items-center cursor-pointer select-none">
          <input
            type="checkbox"
            v-model="filters.onlyInconsistent"
            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          />
          <span class="ml-2 text-sm text-gray-700 font-medium">Apenas grupos inconsistentes</span>
        </label>
      </div>

      <!-- Limpar Filtros -->
      <button
        v-if="hasActiveFilters"
        @click="clearFilters"
        class="text-sm text-red-600 hover:text-red-800 font-medium transition"
      >
        Limpar Filtros
      </button>
    </div>

    <!-- Indicadores de Totais Rápidos -->
    <div class="grid grid-cols-3 gap-4">
      <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 text-center">
        <span class="block text-sm font-medium text-gray-500">Total de Itens</span>
        <span class="text-xl font-bold text-gray-900">{{ importData.total_items }}</span>
      </div>
      <div class="bg-green-50 p-4 rounded-lg border border-green-100 text-center">
        <span class="block text-sm font-medium text-green-700">Válidos</span>
        <span class="text-xl font-bold text-green-900">{{ importData.valid_items }}</span>
      </div>
      <div class="bg-red-50 p-4 rounded-lg border border-red-100 text-center">
        <span class="block text-sm font-medium text-red-700">Inconsistentes</span>
        <span class="text-xl font-bold text-red-900">{{ importData.invalid_items }}</span>
      </div>
    </div>

    <!-- Lista de Accordions por Grupo (Funcionário + Data) -->
    <div class="space-y-3">
      <div
        v-for="(group, index) in filteredGroups"
        :key="index"
        class="bg-white rounded-lg border shadow-sm overflow-hidden"
        :class="group.has_inconsistency ? 'border-red-200' : 'border-gray-200'"
      >
        <!-- Cabeçalho do Accordion (Começa fechado por padrão) -->
        <div
          @click="toggleGroup(index)"
          class="p-4 flex items-center justify-between cursor-pointer select-none transition hover:bg-gray-50"
          :class="group.has_inconsistency ? 'bg-red-50/40' : 'bg-gray-50/10'"
        >
          <div class="flex items-center space-x-3 flex-wrap gap-y-2">
            <!-- Seta Expandir/Recolher -->
            <span class="text-gray-400 transition-transform duration-200" :class="{ 'transform rotate-90': openedGroups.includes(index) }">
              ▶
            </span>
            <span class="font-semibold text-gray-900">{{ group.employee_name }}</span>
            <span class="text-sm text-gray-500 bg-gray-100 px-2 py-0.5 rounded">{{ group.date_label }}</span>

            <!-- Resumo dos Horários no Cabeçalho -->
            <div class="flex items-center space-x-1 text-xs">
              <span class="text-gray-400 mr-1">Batidas:</span>
              <span v-for="time in group.times" :key="time" class="bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-mono font-medium">
                {{ time }}
              </span>
            </div>
          </div>

          <!-- Badges de Alerta do Lado Direito -->
          <div class="flex items-center space-x-3">
            <span
              v-if="group.inconsistencies_count > 0"
              class="px-2.5 py-1 text-xs font-bold rounded-md bg-red-100 text-red-800"
            >
              {{ group.inconsistencies_count }} {{ group.inconsistencies_count === 1 ? 'Inconsistência' : 'Inconsistências' }}
            </span>
            <span
              v-else
              class="px-2.5 py-1 text-xs font-bold rounded-md bg-green-100 text-green-800"
            >
              OK
            </span>
          </div>
        </div>

        <!-- Conteúdo Interno Ocultável do Accordion -->
        <div v-show="openedGroups.includes(index)" class="border-t border-gray-100 bg-white p-4 space-y-4">

          <!-- Formulário de Ajuste em Lote se o funcionário existir -->
          <div v-if="group.can_adjust_times" class="bg-gray-50 p-3 rounded border border-gray-200">
            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-600 mb-2">Ajustar horários em bloco (Quantidade Par)</h4>
            <div class="flex flex-wrap gap-2 items-center">
              <div v-for="(t, tIdx) in groupAdjustments[index]" :key="tIdx" class="flex items-center bg-white border rounded p-1 shadow-sm">
                <input
                  type="text"
                  v-model="groupAdjustments[index][tIdx]"
                  placeholder="00:00"
                  class="w-14 text-center text-sm border-0 p-0 focus:ring-0 font-mono"
                />
                <button type="button" @click="removeTimeField(index, tIdx)" class="text-red-500 hover:text-red-700 text-xs px-1">×</button>
              </div>
              <button type="button" @click="addTimeField(index)" class="text-xs bg-gray-200 text-gray-700 px-2 py-1 rounded hover:bg-gray-300 font-medium">
                + Adicionar Horário
              </button>
              <button
                type="button"
                @click="submitAdjustTimes(group.employee_id, group.date_key, groupAdjustments[index])"
                class="ml-auto text-xs bg-indigo-600 text-white px-3 py-1 rounded hover:bg-indigo-700 font-medium shadow-sm"
              >
                Salvar Horários
              </button>
            </div>
          </div>

          <!-- Tabela de Linhas Originais pertencentes a este Grupo -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
              <thead class="bg-gray-50 text-gray-600 font-medium text-left">
                <tr>
                  <th class="px-3 py-2">Linha</th>
                  <th class="px-3 py-2">Horário Lido</th>
                  <th class="px-3 py-2">Status</th>
                  <th class="px-3 py-2">Divergência / Motivo</th>
                  <th class="px-3 py-2 text-right">Ações</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 text-gray-700">
                <tr v-for="item in group.items" :key="item.id" :class="{'bg-red-50/20': item.status === 'invalid'}">
                  <td class="px-3 py-2 font-mono text-xs text-gray-400">
                    {{ item.line_number_label }}
                  </td>
                  <td class="px-3 py-2 font-mono font-medium">
                    {{ item.time || '—' }}
                  </td>
                  <td class="px-3 py-2">
                    <span
                      class="px-2 py-0.5 rounded text-xs font-semibold"
                      :class="{
                        'bg-red-100 text-red-800': item.status === 'invalid',
                        'bg-green-100 text-green-800': item.status === 'valid',
                        'bg-gray-100 text-gray-800': item.status === 'ignored',
                        'bg-blue-100 text-blue-800': item.status === 'resolved' || item.status === 'launched'
                      }"
                    >
                      {{ item.status_label }}
                    </span>
                  </td>
                  <td class="px-3 py-2 text-xs text-gray-600">
                    {{ item.divergence_reason || '—' }}
                    <div v-if="item.raw_line" class="text-gray-400 font-mono text-[10px] mt-0.5 truncate max-w-md" :title="item.raw_line">
                      String: {{ item.raw_line }}
                    </div>
                  </td>
                  <td class="px-3 py-2 text-right space-x-2">
                    <!-- Ação: Vincular Funcionário (Quando não localizado no AFD) -->
                    <div v-if="item.can_resolve_employee" class="inline-block text-left">
                      <select
                        @change="resolveEmployee(item.id, $event.target.value)"
                        class="text-xs rounded border-gray-300 p-1 focus:ring-indigo-500 focus:border-indigo-500"
                      >
                        <option value="">Vincular funcionário...</option>
                        <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                          {{ emp.label }}
                        </option>
                      </select>
                    </div>

                    <!-- Ação: Ignorar Linha Divergente -->
                    <button
                      v-if="item.can_ignore"
                      @click="ignoreItem(item.id)"
                      class="text-xs text-gray-500 hover:text-red-600 font-medium border border-gray-200 px-2 py-1 rounded hover:bg-gray-50"
                    >
                      Desconsiderar
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

        </div>
      </div>

      <!-- Estado Vazio -->
      <div v-if="filteredGroups.length === 0" class="text-center py-12 bg-white rounded-lg border text-gray-500 text-sm">
        Nenhum grupo de ponto atende aos filtros definidos.
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

// Definição das Props enviadas diretamente pelo ClockRecordImportController
const props = defineProps({
  import: { type: Object, required: true },
  groups: { type: Array, required: true },
  employees: { type: Array, required: true }
})

// Atalhos reativos para os dados
const importData = computed(() => props.import)
const allGroups = computed(() => props.groups)

// Filtros locais com estado inicial reativo
const filters = ref({
  employee: '',
  onlyInconsistent: false
})

// Controla quais Accordions estão abertos (Lista de índices). Começa vazia = Todos recolhidos.
const openedGroups = ref([])

// Estrutura para conter as inputs dinâmicas de ajuste de horário por bloco de cada grupo
const groupAdjustments = ref({})

// Inicializa ou limpa as configurações de inputs locais para ajuste manual de horários
const syncAdjustmentsStructure = () => {
  allGroups.value.forEach((group, index) => {
    // Clona os horários existentes para os inputs reativos
    groupAdjustments.value[index] = group.times ? [...group.times] : []
  })
}

onMounted(() => {
  // Carrega filtros salvos no LocalStorage para persistência enquanto o usuário limpa ou recarrega a tela
  const savedFilters = localStorage.getItem(`import_filters_${importData.value.id}`)
  if (savedFilters) {
    try { filters.value = JSON.parse(savedFilters) } catch (e) {}
  }
  syncAdjustmentsStructure()
})

// Observa mudanças nos filtros e persiste no LocalStorage local do navegador
watch(filters, (newVal) => {
  localStorage.setItem(`import_filters_${importData.value.id}`, JSON.stringify(newVal))
}, { deep: true })

// Sincroniza campos se os dados vindos do backend mudarem pós-atualização
watch(allGroups, () => {
  syncAdjustmentsStructure()
}, { deep: true })

// Computa se há qualquer filtro ativo na tela
const hasActiveFilters = computed(() => {
  return filters.value.employee.trim() !== '' || filters.value.onlyInconsistent
})

// Limpa filtros reativos e remove do LocalStorage
const clearFilters = () => {
  filters.value.employee = ''
  filters.value.onlyInconsistent = false
}

// Filtra a lista de grupos dinamicamente em memória (Client Side) sem disparar requisições pesadas ao servidor
const filteredGroups = computed(() => {
  return allGroups.value.map(group => {
    // Avalia se o grupo contém itens inválidos/com inconsistência
    const hasInconsistency = group.items.some(item => item.status === 'invalid')
    const inconsistenciesCount = group.items.filter(item => item.status === 'invalid').length

    return {
      ...group,
      has_inconsistency: hasInconsistency,
      inconsistencies_count: inconsistenciesCount
    }
  }).filter(group => {
    // Filtro 1: Nome/Matrícula do Funcionário
    if (filters.value.employee.trim() !== '') {
      const search = filters.value.employee.toLowerCase()
      if (!group.employee_name.toLowerCase().includes(search)) {
        return false
      }
    }
    // Filtro 2: Apenas Inconsistentes
    if (filters.value.onlyInconsistent && !group.has_inconsistency) {
      return false
    }
    return true
  })
})

// Abre/Fecha a exibição do Accordion específico
const toggleGroup = (index) => {
  if (openedGroups.value.includes(index)) {
    openedGroups.value = openedGroups.value.filter(i => i !== index)
  } else {
    openedGroups.value.push(index)
  }
}

// Lógica dos campos de horários dinâmicos em bloco
const addTimeField = (index) => {
  groupAdjustments.value[index].push('')
}
const removeTimeField = (index, tIdx) => {
  groupAdjustments.value[index].splice(tIdx, 1)
}

/**
 * PROCESSAMENTO DE SUBMISSÕES - UTILIZA O PRESERVE-SCROLL E STATE DO INERTIA
 * IMPEDE O COMPORTAMENTO DE VOLTAR À PAGINA INDEX E RETÉM O FOCO DO OPERADOR
 */

// Desconsiderar Item AFD[cite: 3, 6]
const ignoreItem = (itemId) => {
  if (!confirm('Deseja desconsiderar este registro de ponto na importação?')) return

  router.post(route('worktime.clock-record-imports.items.ignore', {
    clockRecordImport: importData.value.id,
    clockRecordImportItem: itemId
  }), {}, {
    preserveScroll: true,
    preserveState: true
  })
}

// Vincular funcionário não identificado[cite: 3, 6]
const resolveEmployee = (itemId, employeeId) => {
  if (!employeeId) return

  router.post(route('worktime.clock-record-imports.items.resolve-employee', {
    clockRecordImport: importData.value.id,
    clockRecordImportItem: itemId
  }), {
    employee_id: employeeId
  }, {
    preserveScroll: true,
    preserveState: true
  })
}

// Ajustar horários em lote de um dia[cite: 3, 6]
const submitAdjustTimes = (employeeId, dateKey, timesArray) => {
  // Filtra entradas vazias
  const cleanedTimes = timesArray.filter(t => t && t.trim() !== '')

  if (cleanedTimes.length % 2 !== 0) {
    alert('A quantidade de horários informada deve ser PAR para fechar os pares de entrada e saída.')
    return
  }

  router.post(route('worktime.clock-record-imports.adjust-times', {
    clockRecordImport: importData.value.id
  }), {
    employee_id: employeeId,
    date: dateKey,
    times: cleanedTimes
  }, {
    preserveScroll: true,
    preserveState: true
  })
}

// Lançar os registros definitivamente para o espelho de ponto[cite: 3, 6]
const launchImport = () => {
  if (!confirm('Deseja lançar todos os registros válidos desta importação no sistema?')) return

  router.post(route('worktime.clock-record-imports.launch', {
    clockRecordImport: importData.value.id
  }), {}, {
    preserveScroll: true
  })
}

// Reverter o lançamento do lote[cite: 3, 6]
const revertImport = () => {
  if (!confirm('Atenção! Deseja reverter todos os pontos lançados por este arquivo?')) return

  router.post(route('worktime.clock-record-imports.reverted', {
    clockRecordImport: importData.value.id
  }), {}, {
    preserveScroll: true
  })
}
</script>
