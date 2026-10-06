<script setup>
import { ref, defineProps, onMounted, onBeforeUnmount, computed, shallowRef, onUnmounted, watch, nextTick } from 'vue'
import { Head, router as inertiaRouter } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import { useLayout } from '@/Layouts/composables/layout';
import axios from 'axios';
import { io } from 'socket.io-client';
import { useToast } from 'primevue';
import Toast from 'primevue/toast';
import Panel from 'primevue/panel';
import Badge from 'primevue/badge';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import * as echarts from 'echarts';
import Terminal from 'primevue/terminal';
import TerminalService from 'primevue/terminalservice';

const props = defineProps({
    router: [Object, Array, String],
    routers: [Object, Array, String],
    routers_list: Array,
    subnets: Array,
    cidrs: Array,
    categories: Array,
    user: Object,
})

const toast = useToast()
const { layoutConfig } = useLayout()
const isDarkMode = computed(() => layoutConfig.darkTheme)

// Active Tab: 'ipam' or 'router'
const activeTab = ref('ipam')

// ==========================================
// 1. IPAM & SUBNET VISUALIZER STATE
// ==========================================
const subnetList = ref(props.subnets || [])
const selectedSubnetUuid = ref(subnetList.value.length > 0 ? subnetList.value[0].uuid : null)
const currentSubnet = ref(null)
const gridCells = ref([])
const gridStats = ref({
    total: 0,
    usable: 0,
    used: 0,
    free: 0,
    reserved: 0,
    utilization: 0,
})
const loadingGrid = ref(false)
const searchIpQuery = ref('')
const filterType = ref('all') // 'all', 'available', 'assigned', 'unregistered_active', 'live', 'offline'
const gridDensity = ref('compact') // 'compact' (-35% size) or 'normal'
const syncingMikrotik = ref(false)
const scanningSubnet = ref(false)
const claimingIps = ref(false)

// Modals
const ipDetailDialog = ref(false)
const selectedCell = ref(null)
const isEditing = ref(false)
const formAssignment = ref({
    uuid_ip: '',
    assigned_ip: '',
    device: '',
    kategori: 'PC/Laptop',
    status: 'Static',
    mac_address: '',
    keterangan: '',
})

const newSubnetDialog = ref(false)
const formSubnet = ref({
    network_ip: '',
    cidr: 24,
    keterangan: '',
})
const savingSubnet = ref(false)
const savingAssignment = ref(false)

// IPAM DataTable filters
const ipTableFilters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
})

const subnetOptions = computed(() => {
    return subnetList.value.map(s => ({
        label: `${s.network_ip}/${s.cidr} - ${s.keterangan || 'Subnet'} (${s.ip_assignment_count || 0} Terpakai)`,
        value: s.uuid,
    }))
})

const cidrOptions = computed(() => {
    if (props.cidrs && props.cidrs.length > 0) {
        return props.cidrs.map(c => ({
            label: `/${c.cidr} (${c.subnet_mask} - ${c.usable_host} host)`,
            value: c.cidr,
        }))
    }
    return [
        { label: '/24 (255.255.255.0 - 254 host)', value: 24 },
        { label: '/25 (255.255.255.128 - 126 host)', value: 25 },
        { label: '/26 (255.255.255.192 - 62 host)', value: 26 },
        { label: '/27 (255.255.255.224 - 30 host)', value: 27 },
        { label: '/28 (255.255.255.240 - 14 host)', value: 28 },
        { label: '/29 (255.255.255.248 - 6 host)', value: 29 },
    ]
})

const subnetPreview = computed(() => {
    const cidr = parseInt(formSubnet.value.cidr) || 24
    const total = Math.pow(2, 32 - cidr)
    const usable = Math.max(0, total - 2)
    
    // Hitung representasi string subnet mask
    const maskLong = ~((1 << (32 - cidr)) - 1) >>> 0
    const m1 = (maskLong >>> 24) & 255
    const m2 = (maskLong >>> 16) & 255
    const m3 = (maskLong >>> 8) & 255
    const m4 = maskLong & 255
    const maskStr = `${m1}.${m2}.${m3}.${m4}`

    return {
        cidr,
        total,
        usable,
        mask: maskStr,
    }
})

const categoryOptions = computed(() => {
    return props.categories || [
        'PC/Laptop',
        'Server',
        'Access Point',
        'CCTV',
        'Printer',
        'Switch/Router',
        'MikroTik Discovered',
        'Lainnya',
    ]
})

const statusOptions = ['Static', 'DHCP', 'Reserved']

// Fetch Subnet Grid from API
const loadSubnetGrid = async (uuid) => {
    if (!uuid) return
    loadingGrid.value = true
    try {
        const response = await axios.get(`/network/grid/${uuid}`)
        currentSubnet.value = response.data.subnet
        gridStats.value = response.data.stats
        gridCells.value = response.data.cells
    } catch (error) {
        console.error('Error loading subnet grid:', error)
        toast.add({
            severity: 'error',
            summary: 'Gagal Memuat Grid',
            detail: error.response?.data?.error || 'Tidak dapat memuat data subnet.',
            life: 3000,
        })
    } finally {
        loadingGrid.value = false
    }
}

watch(selectedSubnetUuid, (newUuid) => {
    if (newUuid) {
        loadSubnetGrid(newUuid)
    }
})

// Filtered Cells for the Grid
const filteredCells = computed(() => {
    let cells = gridCells.value
    if (!cells || cells.length === 0) return []

    // Search query filter (matches IP, host octet, Device name, MAC, or category)
    if (searchIpQuery.value && searchIpQuery.value.trim() !== '') {
        const q = searchIpQuery.value.toLowerCase().trim()
        cells = cells.filter(c => {
            const matchIp = c.ip.toLowerCase().includes(q)
            const matchHost = String(c.host).toLowerCase().includes(q)
            const matchDevice = c.assignment?.device?.toLowerCase().includes(q)
            const matchMac = c.assignment?.mac_address?.toLowerCase().includes(q)
            const matchKategori = c.assignment?.kategori?.toLowerCase().includes(q)
            return matchIp || matchHost || matchDevice || matchMac || matchKategori
        })
    }

    // Type filter
    if (filterType.value === 'available') {
        cells = cells.filter(c => c.type === 'available')
    } else if (filterType.value === 'assigned') {
        cells = cells.filter(c => c.type === 'assigned' || (c.type === 'gateway' && c.assignment))
    } else if (filterType.value === 'unregistered_active') {
        cells = cells.filter(c => c.type === 'unregistered_active')
    } else if (filterType.value === 'live') {
        cells = cells.filter(c => c.live_status === 'online' || c.is_live)
    } else if (filterType.value === 'offline') {
        cells = cells.filter(c => c.live_status === 'offline' && (c.type === 'assigned' || c.type === 'gateway'))
    }

    return cells
})

// Table of only assigned IPs on this subnet (termasuk Gateway yang teralokasi)
const assignedTableData = computed(() => {
    return gridCells.value
        .filter(c => Boolean(c.assignment))
        .map(c => ({
            host: c.host,
            ip: c.ip,
            device: c.assignment.device,
            kategori: c.assignment.kategori,
            status: c.assignment.status,
            mac_address: c.assignment.mac_address,
            hostname: c.assignment.hostname,
            source: c.assignment.source,
            last_seen: c.assignment.last_seen,
            keterangan: c.assignment.keterangan,
            is_live: c.is_live,
            live_status: c.live_status,
            is_gateway: c.is_gateway || c.type === 'gateway',
            assignment: c.assignment,
        }))
})

// Open Cell Detail Modal
const openCellDetail = (cell) => {
    selectedCell.value = cell
    if (cell.assignment) {
        formAssignment.value = {
            uuid_ip: currentSubnet.value.uuid,
            assigned_ip: cell.ip,
            device: cell.assignment.device,
            kategori: cell.assignment.kategori || 'PC/Laptop',
            status: cell.assignment.status || 'Static',
            mac_address: cell.assignment.mac_address || '',
            keterangan: cell.assignment.keterangan || '',
        }
        isEditing.value = false
    } else if (cell.type === 'unregistered_active') {
        const octets = cell.ip.split('.')
        const lastOct = octets[octets.length - 1]
        formAssignment.value = {
            uuid_ip: currentSubnet.value.uuid,
            assigned_ip: cell.ip,
            device: `Discovered Host (.${lastOct})`,
            kategori: 'PC/Laptop',
            status: 'Static',
            mac_address: '',
            keterangan: 'Host aktif terdeteksi saat audit ICMP ping',
        }
        isEditing.value = true
    } else {
        formAssignment.value = {
            uuid_ip: currentSubnet.value.uuid,
            assigned_ip: cell.ip,
            device: '',
            kategori: 'PC/Laptop',
            status: 'Static',
            mac_address: '',
            keterangan: '',
        }
        isEditing.value = true
    }
    ipDetailDialog.value = true
}

// Save Assignment
const saveAssignment = async () => {
    if (!formAssignment.value.device) {
        toast.add({ severity: 'warn', summary: 'Peringatan', detail: 'Nama Perangkat harus diisi.', life: 3000 })
        return
    }
    savingAssignment.value = true
    try {
        const response = await axios.post('/network/assign', formAssignment.value)
        toast.add({ severity: 'success', summary: 'Sukses', detail: response.data.message, life: 3000 })
        ipDetailDialog.value = false
        await loadSubnetGrid(selectedSubnetUuid.value)
        refreshSubnetList()
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Gagal', detail: error.response?.data?.message || 'Terjadi kesalahan.', life: 3000 })
    } finally {
        savingAssignment.value = false
    }
}

// Release / Unassign IP
const releaseIp = async (ip) => {
    if (!confirm(`Yakin ingin melepaskan alokasi untuk IP ${ip}?`)) return
    try {
        const response = await axios.post('/network/release', { assigned_ip: ip })
        toast.add({ severity: 'info', summary: 'Dilepaskan', detail: response.data.message, life: 3000 })
        ipDetailDialog.value = false
        await loadSubnetGrid(selectedSubnetUuid.value)
        refreshSubnetList()
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Gagal', detail: 'Tidak dapat melepaskan IP.', life: 3000 })
    }
}

// Create New Subnet
const saveNewSubnet = async () => {
    if (!formSubnet.value.network_ip) {
        toast.add({ severity: 'warn', summary: 'Peringatan', detail: 'Alamat Network IP wajib diisi.', life: 3000 })
        return
    }
    savingSubnet.value = true
    try {
        const response = await axios.post('/network/subnet/save', formSubnet.value)
        toast.add({ severity: 'success', summary: 'Subnet Dibuat', detail: response.data.message, life: 3000 })
        newSubnetDialog.value = false
        formSubnet.value = { network_ip: '', cidr: 24, keterangan: '' }
        
        inertiaRouter.reload({
            only: ['subnets'],
            onSuccess: (page) => {
                subnetList.value = page.props.subnets
                if (response.data.subnet) {
                    selectedSubnetUuid.value = response.data.subnet.uuid
                }
            }
        })
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Gagal Menambah Subnet', detail: error.response?.data?.error || error.response?.data?.message || 'Gagal menyimpan.', life: 3000 })
    } finally {
        savingSubnet.value = false
    }
}

// Delete Current Subnet
const deleteCurrentSubnet = async () => {
    if (!currentSubnet.value) return
    if (!confirm(`Hapus subnet ${currentSubnet.value.network_ip}/${currentSubnet.value.cidr} beserta semua alokasi di dalamnya?`)) return
    try {
        const response = await axios.post(`/network/subnet/delete/${currentSubnet.value.uuid}`)
        toast.add({ severity: 'warn', summary: 'Subnet Dihapus', detail: response.data.message, life: 3000 })
        inertiaRouter.reload({
            only: ['subnets'],
            onSuccess: (page) => {
                subnetList.value = page.props.subnets
                selectedSubnetUuid.value = subnetList.value.length > 0 ? subnetList.value[0].uuid : null
            }
        })
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Gagal Menghapus Subnet', detail: 'Terjadi kesalahan.', life: 3000 })
    }
}

// Sync with MikroTik DHCP & ARP
const syncMikrotik = async () => {
    syncingMikrotik.value = true
    try {
        const response = await axios.post('/network/sync-mikrotik')
        toast.add({
            severity: 'success',
            summary: 'Sinkronisasi MikroTik Berhasil',
            detail: response.data.message,
            life: 5000,
        })
        await loadSubnetGrid(selectedSubnetUuid.value)
        refreshSubnetList()
    } catch (error) {
        toast.add({
            severity: 'warn',
            summary: 'Peringatan MikroTik',
            detail: error.response?.data?.message || 'Tidak dapat terhubung ke MikroTik API.',
            life: 4000,
        })
    } finally {
        syncingMikrotik.value = false
    }
}

// Audit host live via ICMP ping sweep
const auditSubnetLive = async () => {
    if (!selectedSubnetUuid.value) return
    scanningSubnet.value = true
    try {
        const response = await axios.post(`/network/scan-live/${selectedSubnetUuid.value}`)
        toast.add({
            severity: 'success',
            summary: 'Audit Host Selesai',
            detail: response.data.message,
            life: 5000,
        })
        await loadSubnetGrid(selectedSubnetUuid.value)
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Gagal Audit Host',
            detail: error.response?.data?.message || 'Tidak dapat memindai host di subnet.',
            life: 4000,
        })
    } finally {
        scanningSubnet.value = false
    }
}

// Auto-register all discovered unregistered active hosts
const claimAllActiveIps = async () => {
    if (!selectedSubnetUuid.value) return
    claimingIps.value = true
    try {
        const response = await axios.post(`/network/claim-all-active/${selectedSubnetUuid.value}`)
        toast.add({
            severity: 'success',
            summary: 'Pendaftaran Host Berhasil',
            detail: response.data.message,
            life: 5000,
        })
        await loadSubnetGrid(selectedSubnetUuid.value)
        refreshSubnetList()
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Gagal Mendaftarkan Host',
            detail: error.response?.data?.message || 'Gagal mendaftarkan host aktif.',
            life: 4000,
        })
    } finally {
        claimingIps.value = false
    }
}

// Export PDF in new window
const exportPdf = () => {
    if (!currentSubnet.value) return
    window.open(`/network/pdf/${currentSubnet.value.uuid}`, '_blank')
}

// Refresh subnet count in dropdown
const refreshSubnetList = () => {
    inertiaRouter.reload({
        only: ['subnets'],
        onSuccess: (page) => {
            subnetList.value = page.props.subnets
        }
    })
}

// Ping from IPAM
const triggerPing = (ip) => {
    ipDetailDialog.value = false
    ping(ip)
}

// ==========================================
// 2. MIKROTIK ROUTER CRUD & MANAGEMENT
// ==========================================
const routerManagerDialog = ref(false)
const routerFormDialog = ref(false)
const routerList = ref(props.routers_list || [])
const isEditingRouter = ref(false)
const testingConnection = ref(false)
const savingRouter = ref(false)
const connectionTestResult = ref(null)

const formRouter = ref({
    uuid: '',
    name: '',
    host: '',
    port: 8728,
    user: '',
    pass: '',
    keterangan: '',
})

const openAddRouter = () => {
    isEditingRouter.value = false
    connectionTestResult.value = null
    formRouter.value = {
        uuid: '',
        name: '',
        host: '',
        port: 8728,
        user: '',
        pass: '',
        keterangan: '',
    }
    routerFormDialog.value = true
}

const openEditRouter = (router) => {
    isEditingRouter.value = true
    connectionTestResult.value = null
    formRouter.value = {
        uuid: router.uuid,
        name: router.name,
        host: router.host,
        port: router.port,
        user: router.user,
        pass: '', // leave empty unless changing
        keterangan: router.keterangan || '',
    }
    routerFormDialog.value = true
}

const testRouterConnection = async () => {
    if (!formRouter.value.host || !formRouter.value.user) {
        toast.add({ severity: 'warn', summary: 'Input Diperlukan', detail: 'Host dan Username harus diisi.', life: 3000 })
        return
    }
    testingConnection.value = true
    connectionTestResult.value = null
    try {
        const response = await axios.post('/network/router/test', formRouter.value)
        connectionTestResult.value = response.data
        toast.add({
            severity: 'success',
            summary: 'Koneksi Berhasil!',
            detail: `Terhubung ke ${response.data.identity || 'MikroTik'} (${response.data.version || ''})`,
            life: 4000
        })
    } catch (error) {
        const errMsg = error.response?.data?.message || 'Gagal terhubung ke router.'
        connectionTestResult.value = { success: false, message: errMsg }
        toast.add({
            severity: 'error',
            summary: 'Koneksi Gagal',
            detail: errMsg,
            life: 5000
        })
    } finally {
        testingConnection.value = false
    }
}

const saveRouter = async () => {
    if (!formRouter.value.name || !formRouter.value.host || !formRouter.value.user) {
        toast.add({ severity: 'warn', summary: 'Peringatan', detail: 'Nama, Host, dan Username wajib diisi.', life: 3000 })
        return
    }
    if (!isEditingRouter.value && !formRouter.value.pass) {
        toast.add({ severity: 'warn', summary: 'Peringatan', detail: 'Password wajib diisi untuk router baru.', life: 3000 })
        return
    }
    savingRouter.value = true
    try {
        if (isEditingRouter.value) {
            const res = await axios.post(`/network/router/update/${formRouter.value.uuid}`, formRouter.value)
            toast.add({ severity: 'success', summary: 'Router Diperbarui', detail: res.data.message, life: 3000 })
        } else {
            const res = await axios.post('/network/router/store', formRouter.value)
            toast.add({ severity: 'success', summary: 'Router Ditambahkan', detail: res.data.message, life: 3000 })
        }
        routerFormDialog.value = false
        refreshRouterList()
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Gagal Menyimpan', detail: error.response?.data?.message || 'Gagal menyimpan data router.', life: 3000 })
    } finally {
        savingRouter.value = false
    }
}

const deleteRouter = async (router) => {
    if (!confirm(`Hapus router ${router.name} (${router.host})? Riwayat konsumsi bandwidth terkait juga akan dihapus.`)) return
    try {
        const res = await axios.post(`/network/router/delete/${router.uuid}`)
        toast.add({ severity: 'info', summary: 'Dihapus', detail: res.data.message, life: 3000 })
        refreshRouterList()
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Gagal', detail: 'Tidak dapat menghapus router.', life: 3000 })
    }
}

const toggleRouter = async (router) => {
    try {
        const res = await axios.post(`/network/router/toggle/${router.uuid}`)
        toast.add({ severity: 'info', summary: 'Status Diubah', detail: res.data.message, life: 3000 })
        refreshRouterList()
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Gagal', detail: 'Tidak dapat mengubah status.', life: 3000 })
    }
}

const refreshRouterList = () => {
    inertiaRouter.reload({
        only: ['routers_list', 'router'],
        onSuccess: (page) => {
            routerList.value = page.props.routers_list || []
            initRouterData()
        }
    })
}

// ==========================================
// 3. MIKROTIK LIVE TRAFFIC & REAL-TIME STATS
// ==========================================
let socket
const lists = ref(Array())
const chartRefs = shallowRef([])
const chartInstances = shallowRef([])
const options = shallowRef([])
const timerLists = ref([])

// Per-router isolated state (Consumption, Speeds, Interfaces, Menu, and Resources)
const routerStates = ref({})

const getRouterState = (id) => {
    if (id === undefined || id === null) return null
    if (!routerStates.value[id]) {
        routerStates.value[id] = {
            selectedInterface: null,
            selectedDefaultName: null,
            rxFmt: '0 B/s',
            txFmt: '0 B/s',
            rxBps: 0,
            txBps: 0,
            cpu: 0,
            memory: '-',
            disk: '-',
            uptime: '-',
            consumption: {
                today: { total: 0, formatted: '0 B', rx_fmt: '0 B', tx_fmt: '0 B', rx: 0, tx: 0 },
                weekly: { total: 0, formatted: '0 B', rx_fmt: '0 B', tx_fmt: '0 B', rx: 0, tx: 0 },
                monthly: { total: 0, formatted: '0 B', rx_fmt: '0 B', tx_fmt: '0 B', rx: 0, tx: 0 },
            },
            interfaces: [],
            menuItems: [],
        }
    }
    return routerStates.value[id]
}

const isInterfaceRunning = (routerId) => {
    const rState = getRouterState(routerId)
    if (!rState || !rState.interfaces || !rState.selectedInterface) return false
    const iface = rState.interfaces.find(i => i.name === rState.selectedInterface || i.default_name === rState.selectedInterface)
    return iface ? (iface.running === 'true' || iface.running === true) : false
}

const formatBytesStatic = (bytes) => {
    const num = Number(bytes)
    if (!num || isNaN(num) || num <= 0) return '0 B'
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
    const i = Math.min(Math.floor(Math.log(num) / Math.log(1024)), sizes.length - 1)
    return parseFloat((num / Math.pow(1024, i)).toFixed(2)) + ' ' + sizes[i]
}

// Total aggregate consumption across all active routers
const totalConsumption = computed(() => {
    let today = 0, todayRx = 0, todayTx = 0
    let weekly = 0, weeklyRx = 0, weeklyTx = 0
    let monthly = 0, monthlyRx = 0, monthlyTx = 0

    Object.values(routerStates.value).forEach(st => {
        if (st && st.consumption) {
            today += Number(st.consumption.today?.total || 0)
            todayRx += Number(st.consumption.today?.rx || 0)
            todayTx += Number(st.consumption.today?.tx || 0)

            weekly += Number(st.consumption.weekly?.total || 0)
            weeklyRx += Number(st.consumption.weekly?.rx || 0)
            weeklyTx += Number(st.consumption.weekly?.tx || 0)

            monthly += Number(st.consumption.monthly?.total || 0)
            monthlyRx += Number(st.consumption.monthly?.rx || 0)
            monthlyTx += Number(st.consumption.monthly?.tx || 0)
        }
    })

    return {
        today: { formatted: formatBytesStatic(today), rx_fmt: formatBytesStatic(todayRx), tx_fmt: formatBytesStatic(todayTx) },
        weekly: { formatted: formatBytesStatic(weekly), rx_fmt: formatBytesStatic(weeklyRx), tx_fmt: formatBytesStatic(weeklyTx) },
        monthly: { formatted: formatBytesStatic(monthly), rx_fmt: formatBytesStatic(monthlyRx), tx_fmt: formatBytesStatic(monthlyTx) },
    }
})

const networkDlg = ref(false)
const networkHeader = ref(null)
const tbList = ref(Array())
const loading = ref(true)
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    address: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
    interface: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
    network: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
})
const pingResults = ref([])
const statusMessage = ref('')
const pingAddress = ref(null)
const isLoading = ref(false)
const connectionStatus = ref('Connecting...')
const sessionId = ref(null)

const terminalDlg = ref(false)
const terminalHeader = ref(null)

const initRouterData = () => {
    lists.value = []
    options.value = []
    let parsing = []
    try {
        const raw = props.routers || props.router || []
        parsing = typeof raw === 'string' ? JSON.parse(raw) : (raw || [])
    } catch (e) {
        parsing = []
    }
    if (parsing.length > 0) {
        parsing.forEach((ls, lIndex) => {
            lists.value.push(ls)
            options.value.push(initOption())

            if (ls && ls.data && ls.data.length > 0) {
                const rState = getRouterState(ls.id)
                rState.interfaces = ls.data
                if (ls.consumption) {
                    rState.consumption = ls.consumption
                }

                // Auto-select first running interface or first interface
                if (!rState.selectedInterface) {
                    const runningIf = ls.data.find(i => (i.running === 'true' || i.running === true) && i.disabled !== 'true')
                    const firstIf = runningIf || ls.data[0]
                    rState.selectedInterface = firstIf.name
                    rState.selectedDefaultName = firstIf.default_name || firstIf.name
                }

                // Build menu items specifically for THIS router
                rState.menuItems = ls.data.map((item) => ({
                    label: `${item.name} (${item.type}${item.running === 'true' || item.running === true ? ' • LINK UP' : ''})`,
                    icon: (item.running === 'true' || item.running === true) ? 'pi pi-bolt text-emerald-500' : 'pi pi-minus text-slate-400',
                    command: () => {
                        changeRouterGraph(ls.id, lIndex, item.name, item.default_name || item.name)
                    }
                }))
            }
        })
    }
}

onMounted(() => {
    if (selectedSubnetUuid.value) {
        loadSubnetGrid(selectedSubnetUuid.value)
    }

    initRouterData()
    initCharts()

    // Start interval monitoring for all active routers
    lists.value.forEach((ls, lIndex) => {
        if (ls && ls.id !== undefined && ls.id !== null) {
            setUpdate(ls.id, lIndex)
        }
    })

    const pingSocketUrl = import.meta.env.VITE_PING_SOCKET_URL || (window.location.protocol + '//' + window.location.hostname + ':5005');
    socket = io(pingSocketUrl, {
        transports: ['websocket', 'polling']
    });

    socket.on('connect', () => {
        connectionStatus.value = 'Connected'
    });

    socket.on('disconnect', () => {
        connectionStatus.value = 'Disconnected'
        isLoading.value = false
    });

    socket.on('sessionId', (id) => {
        sessionId.value = id
    });

    socket.on('ping_result', (data) => {
        if (data.session === sessionId.value) {
            statusMessage.value += (data.result + '\n')
            isLoading.value = true
        }
    });

    socket.on('ping_stopped', (data) => {
        if (data.session === sessionId.value) {
            isLoading.value = false
        }
    });

    socket.on('ping_error', (data) => {
        statusMessage.value = data.message
        isLoading.value = false
    });
})

onUnmounted(() => {
    if (socket) {
        socket.disconnect()
    }
    timerLists.value.forEach(t => clearInterval(t))
})

onBeforeUnmount(() => {
    disposeCharts()
})

const setUpdate = async (id, index) => {
    if (id !== undefined && id !== null && id > -1) {
        if (timerLists.value[id]) {
            clearInterval(timerLists.value[id])
        }
        const rState = getRouterState(id)
        const targetIface = rState?.selectedInterface || ''
        try {
            const response = await axios.post('/network/graphic', { id: id, name: targetIface })
            const res = response.data
            if (res) {
                updateChartForRouter(index, res.time, res.rx, res.tx)
                if (rState) {
                    rState.rxFmt = res.rx_fmt || '0 B/s'
                    rState.txFmt = res.tx_fmt || '0 B/s'
                    rState.rxBps = res.rx || 0
                    rState.txBps = res.tx || 0

                    if (res.consumption) {
                        rState.consumption = res.consumption
                    }

                    if (res.resource) {
                        const rsc = res.resource
                        rState.cpu    = rsc.cpu_load
                        rState.memory = rsc.memory
                        rState.disk   = rsc.hdd
                        rState.uptime = rsc.uptime
                    }
                }

                timerLists.value[id] = setInterval(() => {
                    setUpdate(id, index)
                }, 5000)
            }
        } catch (error) {
            timerLists.value[id] = setInterval(() => {
                setUpdate(id, index)
            }, 8000)
        }
    }
}

// Vibrant theme colors for ECharts (Downstream: Cyan/Blue, Upstream: Violet/Rose)
const colors = ['#06b6d4', '#ec4899'];
const formatter = (bytes) => {
    const num = Number(bytes)
    if (!num || isNaN(num) || num === 0) return '0 bps'
    const sizes = ['bps', 'Kbps', 'Mbps', 'Gbps', 'Tbps']
    const i = parseInt(Math.floor(Math.log(num) / Math.log(1024)))
    const clampedI = Math.min(i, sizes.length - 1)
    return parseFloat((num / Math.pow(1024, clampedI)).toFixed(2)) + ' ' + sizes[clampedI]
}

const initOption = () => {
    const isDark = Boolean(isDarkMode.value)
    return {
        color: colors,
        updates: 0,
        tooltip: {
            trigger: 'axis',
            axisPointer: { type: 'cross' },
            backgroundColor: isDark ? '#1e293b' : '#ffffff',
            borderColor: isDark ? '#334155' : '#e2e8f0',
            textStyle: { color: isDark ? '#f8fafc' : '#0f172a' },
            padding: 12,
            formatter: function (params) {
                if (!params || !params.length) return ''
                const _rx = formatter(params[0]?.value || 0)
                const _tx = formatter(params[1]?.value || 0)
                const _time = params[0]?.axisValueLabel || params[0]?.name || '-'
                return `<div style="font-size: 11px;">
                    <b>Waktu: ${_time}</b><br/>
                    <span style="color:#06b6d4; font-weight:bold;">● Downstream (Rx):</span> ${_rx}<br/>
                    <span style="color:#ec4899; font-weight:bold;">● Upstream (Tx):</span> ${_tx}
                </div>`
            }
        },
        legend: {
            data: ['Downstream (Rx)', 'Upstream (Tx)'],
            textStyle: { color: isDark ? '#cbd5e1' : '#475569' },
            top: 10,
        },
        grid: { top: 60, bottom: 40, left: 85, right: 25 },
        xAxis: {
            type: 'category',
            axisTick: { alignWithLabel: true },
            axisLine: { lineStyle: { color: isDark ? '#475569' : '#cbd5e1' } },
            axisLabel: { color: isDark ? '#94a3b8' : '#64748b' },
            data: []
        },
        yAxis: [
            {
                type: 'value',
                boundaryGap: ['0%', '20%'],
                splitLine: { lineStyle: { color: isDark ? '#334155' : '#f1f5f9' } },
                axisLabel: {
                    color: isDark ? '#94a3b8' : '#64748b',
                    formatter: function (v) { return formatter(v) }
                }
            }
        ],
        series: [
            {
                name: 'Downstream (Rx)',
                type: 'line',
                smooth: true,
                showSymbol: false,
                areaStyle: {
                    color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                        { offset: 0, color: 'rgba(6, 182, 212, 0.45)' },
                        { offset: 1, color: 'rgba(6, 182, 212, 0.02)' }
                    ])
                },
                lineStyle: { width: 2.5, color: '#06b6d4' },
                data: []
            },
            {
                name: 'Upstream (Tx)',
                type: 'line',
                smooth: true,
                showSymbol: false,
                areaStyle: {
                    color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                        { offset: 0, color: 'rgba(236, 72, 153, 0.45)' },
                        { offset: 1, color: 'rgba(236, 72, 153, 0.02)' }
                    ])
                },
                lineStyle: { width: 2.5, color: '#ec4899' },
                data: []
            }
        ]
    }
}

const getOrInitChart = (index) => {
    const el = chartRefs.value[index]
    if (!el) return null
    if (el.clientWidth === 0 || el.clientHeight === 0) return null

    let instance = chartInstances.value[index]
    if (!instance) {
        const existing = echarts.getInstanceByDom(el)
        if (existing) {
            instance = existing
        } else {
            instance = echarts.init(el)
        }
        chartInstances.value[index] = instance
    }
    if (options.value[index]) {
        instance.setOption(options.value[index])
    }
    return instance
}

const renderAllCharts = () => {
    nextTick(() => {
        lists.value.forEach((ls, index) => {
            if (ls) {
                const instance = getOrInitChart(index)
                if (instance) {
                    instance.resize()
                    if (options.value[index]) {
                        instance.setOption(options.value[index], true)
                    }
                }
            }
        })
    })
}

const setChartRef = (el, index) => {
    if (el) {
        chartRefs.value[index] = el
        nextTick(() => {
            getOrInitChart(index)
        })
    } else {
        if (chartInstances.value[index]) {
            chartInstances.value[index].dispose()
            chartInstances.value[index] = null
        }
        delete chartRefs.value[index]
    }
}

const initCharts = () => {
    renderAllCharts()
    window.addEventListener('resize', resizeCharts)
}

const resizeCharts = () => {
    chartInstances.value.forEach((instance, index) => {
        if (instance) {
            instance.resize()
        } else {
            getOrInitChart(index)
        }
    })
}

const disposeCharts = () => {
    window.removeEventListener('resize', resizeCharts)
    chartInstances.value.forEach(instance => {
        if (instance) instance.dispose()
    })
    chartInstances.value = []
}

const updateChartForRouter = (index, label, rx, tx) => {
    const maxPoints = 15
    const chart = options.value[index]
    if (!chart) return
    chart.updates++
    if (chart.xAxis.data.length >= maxPoints) {
        chart.xAxis.data.shift()
        chart.series.forEach(s => s.data.shift())
    }
    chart.xAxis.data.push(label)
    chart.series[0].data.push(parseInt(rx) || 0)
    chart.series[1].data.push(parseInt(tx) || 0)

    const instance = chartInstances.value[index] || getOrInitChart(index)
    if (instance) {
        const { animationDuration, animationEasing, ...rest } = chart
        instance.setOption({
            ...rest,
            animation: true
        }, true)
    }
}

watch(activeTab, (newTab) => {
    if (newTab === 'router') {
        nextTick(() => {
            setTimeout(() => {
                renderAllCharts()
            }, 60)
        })
    }
})

watch(isDarkMode, () => {
    options.value = options.value.map(() => initOption())
    renderAllCharts()
})

const showNetwork = (router, name) => {
    loading.value = true
    if (router && router.length > 0) {
        networkHeader.value = name
        tbList.value = router
        networkDlg.value = true
        loading.value = false
    } else {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Data not found!', life: 3000 })
        loading.value = false
    }
}

const changeRouterGraph = (id, index, label, name) => {
    const rState = getRouterState(id)
    if (rState) {
        rState.selectedInterface = label
        rState.selectedDefaultName = name || label
    }
    const chart = options.value[index]
    if (chart) {
        chart.updates = 0
        chart.xAxis.data = []
        chart.series.forEach(s => s.data = [])
    }
    setUpdate(id, index)
}

const ping = (ip) => {
    if (ip.includes('/')) {
        ip = ip.slice(0, -3)
    }
    if (socket && socket.connected && sessionId.value) {
        pingResults.value = []
        statusMessage.value = `Pinging ${ip} ...\n`
        socket.emit('start_ping', {
            target: ip,
            mode: 'continuous',
            session: sessionId.value
        })
        pingAddress.value = ip
        terminalHeader.value = 'Ping Live Terminal: ' + ip
        terminalDlg.value = true
        isLoading.value = true
    } else {
        toast.add({ severity: 'warn', summary: 'Ping Service', detail: 'Layanan ping belum terhubung ke server.', life: 3000 })
    }
}

const stop = (ip) => {
    if (socket && socket.connected && sessionId.value) {
        socket.emit('stop_ping', { session: sessionId.value })
    }
    isLoading.value = false
    terminalDlg.value = false
}
</script>

<template>
    <Toast />
    <ConfirmDialog />
    <Head title="Manajemen Alokasi Network IP & IPAM" />

    <!-- TOP TITLE & TAB NAVIGATOR -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-surface-900 dark:text-surface-0 flex items-center gap-2">
                <i class="pi pi-sitemap text-primary text-2xl"></i>
                Tata Kelola Jaringan & Visualisasi IPAM
            </h1>
            <p class="text-sm text-muted-color mt-1">
                Visualizer Subnet IP, Alokasi Host Perangkat, Manajemen Router MikroTik Terenkripsi, dan Analitik Bandwidth.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <!-- Router Manager Button -->
            <Button
                label="Kelola Router MikroTik"
                icon="pi pi-cog"
                severity="secondary"
                outlined
                @click="routerManagerDialog = true"
            />

            <!-- Navigation Tabs -->
            <div class="inline-flex p-1 bg-surface-100 dark:bg-surface-800 rounded-xl shadow-inner border border-surface-200 dark:border-surface-700">
                <button
                    @click="activeTab = 'ipam'"
                    :class="[
                        'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-200',
                        activeTab === 'ipam'
                            ? 'bg-primary text-white shadow-md'
                            : 'text-muted-color hover:text-surface-900 dark:hover:text-surface-0'
                    ]"
                >
                    <i class="pi pi-th-large"></i>
                    Visualizer Subnet (IPAM)
                </button>
                <button
                    @click="activeTab = 'router'"
                    :class="[
                        'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-200',
                        activeTab === 'router'
                            ? 'bg-primary text-white shadow-md'
                            : 'text-muted-color hover:text-surface-900 dark:hover:text-surface-0'
                    ]"
                >
                    <i class="pi pi-chart-line"></i>
                    Trafik & Bandwidth
                </button>
            </div>
        </div>
    </div>

    <!-- ==================================================== -->
    <!-- TAB 1: VISUALIZER SUBNET & IPAM                      -->
    <!-- ==================================================== -->
    <div v-show="activeTab === 'ipam'" class="space-y-6">
        <!-- CONTROLS & SUBNET SELECTOR BAR -->
        <Card class="shadow-sm border border-surface-200 dark:border-surface-700">
            <template #content>
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="flex-1 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        <label class="font-semibold text-sm text-surface-900 dark:text-surface-0 whitespace-nowrap">
                            Pilih Subnet:
                        </label>
                        <Select
                            v-model="selectedSubnetUuid"
                            :options="subnetOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Pilih Jaringan Subnet"
                            class="w-full sm:w-96"
                        />
                    </div>

                    <!-- Actions Bar -->
                    <div class="flex flex-wrap items-center gap-2">
                        <Button
                            label="Tambah Subnet"
                            icon="pi pi-plus-circle"
                            severity="primary"
                            @click="newSubnetDialog = true"
                        />
                        <Button
                            label="Audit Host (Ping Sweep)"
                            icon="pi pi-radar"
                            severity="help"
                            :loading="scanningSubnet"
                            @click="auditSubnetLive"
                            v-tooltip.bottom="'Pindai status ICMP ping seluruh host live secara paralel (cepat ~2s)'"
                        />
                        <Button
                            v-if="gridStats.unregistered_active > 0"
                            :label="`Daftarkan ${gridStats.unregistered_active} Host Liar`"
                            icon="pi pi-user-plus"
                            severity="warn"
                            :loading="claimingIps"
                            @click="claimAllActiveIps"
                            v-tooltip.bottom="'Daftarkan semua IP aktif tak terdaftar ke inventaris IPAM'"
                        />
                        <Button
                            label="Sinkronisasi MikroTik"
                            icon="pi pi-sync"
                            severity="info"
                            :loading="syncingMikrotik"
                            @click="syncMikrotik"
                            v-tooltip.bottom="'Tarik DHCP Leases & ARP table dari MikroTik'"
                        />
                        <Button
                            label="Cetak PDF"
                            icon="pi pi-file-pdf"
                            severity="warn"
                            :disabled="!currentSubnet"
                            @click="exportPdf"
                            v-tooltip.bottom="'Cetak Laporan Resmi Alokasi Subnet ini'"
                        />
                        <Button
                            icon="pi pi-trash"
                            severity="danger"
                            outlined
                            :disabled="!currentSubnet || subnetList.length <= 1"
                            @click="deleteCurrentSubnet"
                            v-tooltip.bottom="'Hapus Subnet ini'"
                        />
                    </div>
                </div>
            </template>
        </Card>

        <!-- KPI SUMMARY CARDS (Adaptive Light/Dark Mode) -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <!-- Total Kapasitas -->
            <div class="p-4 bg-surface-0 dark:bg-surface-800 rounded-xl border border-surface-200 dark:border-surface-700/60 shadow-sm flex items-center justify-between transition-colors">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-muted-color">Total Kapasitas</span>
                    <h3 class="text-2xl font-black text-surface-900 dark:text-surface-0 mt-1">
                        {{ gridStats.total }} <span class="text-xs font-normal text-muted-color">IP</span>
                    </h3>
                    <span class="text-xs text-primary font-medium">/{{ currentSubnet?.cidr || '24' }} ({{ currentSubnet?.subnet_mask }})</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400 flex items-center justify-center text-xl">
                    <i class="pi pi-globe"></i>
                </div>
            </div>

            <!-- Terdaftar di IPAM (Assigned) -->
            <div class="p-4 bg-surface-0 dark:bg-surface-800 rounded-xl border border-surface-200 dark:border-surface-700/60 shadow-sm flex items-center justify-between transition-colors">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-rose-500">Terdaftar (IPAM)</span>
                    <h3 class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">
                        {{ gridStats.used }} <span class="text-xs font-normal text-muted-color">Host</span>
                    </h3>
                    <div class="text-[11px] text-muted-color mt-0.5 flex items-center gap-2">
                        <span class="text-emerald-500 font-semibold">🟢 {{ gridStats.online || 0 }} On</span>
                        <span class="text-slate-400">⚪ {{ gridStats.offline || 0 }} Off</span>
                    </div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-500/20 dark:text-rose-400 flex items-center justify-center text-xl">
                    <i class="pi pi-desktop"></i>
                </div>
            </div>

            <!-- Host Liar / Belum Dicatat (Unregistered Active) -->
            <div class="p-4 bg-surface-0 dark:bg-surface-800 rounded-xl border border-surface-200 dark:border-surface-700/60 shadow-sm flex items-center justify-between transition-colors">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-amber-500">Host Liar (Aktif)</span>
                    <h3 class="text-2xl font-black text-amber-500 mt-1">
                        {{ gridStats.unregistered_active || 0 }} <span class="text-xs font-normal text-muted-color">IP</span>
                    </h3>
                    <span class="text-[11px] text-muted-color">Merespons ping / belum dicatat</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400 flex items-center justify-center text-xl">
                    <i class="pi pi-exclamation-triangle"></i>
                </div>
            </div>

            <!-- Tersedia (Free) -->
            <div class="p-4 bg-surface-0 dark:bg-surface-800 rounded-xl border border-surface-200 dark:border-surface-700/60 shadow-sm flex items-center justify-between transition-colors">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-emerald-500">Bebas (Available)</span>
                    <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                        {{ gridStats.free }} <span class="text-xs font-normal text-muted-color">IP</span>
                    </h3>
                    <span class="text-xs text-muted-color">Bersih & siap alokasi</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400 flex items-center justify-center text-xl">
                    <i class="pi pi-check-circle"></i>
                </div>
            </div>

            <!-- Utilisasi Subnet -->
            <div class="p-4 bg-surface-0 dark:bg-surface-800 rounded-xl border border-surface-200 dark:border-surface-700/60 shadow-sm flex flex-col justify-between transition-colors">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-muted-color">Utilisasi Subnet</span>
                    <span class="text-sm font-bold text-surface-900 dark:text-surface-0">{{ gridStats.utilization }}%</span>
                </div>
                <div class="w-full bg-surface-100 dark:bg-surface-700 rounded-full h-3 my-2 overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-500"
                        :style="{ width: `${Math.min(gridStats.utilization, 100)}%` }"
                        :class="[
                            gridStats.utilization > 80 ? 'bg-rose-500' :
                            gridStats.utilization > 50 ? 'bg-amber-500' : 'bg-emerald-500'
                        ]"
                    ></div>
                </div>
                <span class="text-xs text-muted-color">Cadangan Gateway/Net: {{ gridStats.reserved }} IP</span>
            </div>
        </div>

        <!-- SUBNET GRID VISUALIZER CARD -->
        <Card class="shadow-sm border border-surface-200 dark:border-surface-700">
            <template #title>
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-2 border-b border-surface-100 dark:border-surface-700">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></div>
                        <span class="text-lg font-bold text-surface-900 dark:text-surface-0">
                            Peta Alokasi Host IP ({{ currentSubnet?.network_ip }}/{{ currentSubnet?.cidr }})
                        </span>
                    </div>

                    <!-- Search & Filter Controls -->
                    <div class="flex flex-wrap items-center gap-2">
                        <IconField>
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText
                                v-model="searchIpQuery"
                                placeholder="Cari IP / Perangkat..."
                                class="w-48 sm:w-60 p-inputtext-sm text-xs"
                            />
                        </IconField>

                        <div class="flex items-center border border-surface-200 dark:border-surface-700 rounded-lg p-0.5 bg-surface-50 dark:bg-surface-800">
                            <button
                                @click="filterType = 'all'"
                                :class="['px-2.5 py-1 text-xs rounded font-medium', filterType === 'all' ? 'bg-surface-0 dark:bg-surface-700 shadow text-primary' : 'text-muted-color']"
                            >
                                Semua
                            </button>
                            <button
                                @click="filterType = 'assigned'"
                                :class="['px-2.5 py-1 text-xs rounded font-medium', filterType === 'assigned' ? 'bg-surface-0 dark:bg-surface-700 shadow text-rose-500' : 'text-muted-color']"
                            >
                                Terpakai
                            </button>
                            <button
                                @click="filterType = 'available'"
                                :class="['px-2.5 py-1 text-xs rounded font-medium', filterType === 'available' ? 'bg-surface-0 dark:bg-surface-700 shadow text-emerald-500' : 'text-muted-color']"
                            >
                                Bebas
                            </button>
                            <button
                                @click="filterType = 'unregistered_active'"
                                :class="['px-2.5 py-1 text-xs rounded font-medium', filterType === 'unregistered_active' ? 'bg-surface-0 dark:bg-surface-700 shadow text-amber-500 font-bold' : 'text-muted-color']"
                            >
                                ⚠️ Liar (Aktif)
                            </button>
                            <button
                                @click="filterType = 'live'"
                                :class="['px-2.5 py-1 text-xs rounded font-medium', filterType === 'live' ? 'bg-surface-0 dark:bg-surface-700 shadow text-emerald-500' : 'text-muted-color']"
                            >
                                🟢 Online
                            </button>
                            <button
                                @click="filterType = 'offline'"
                                :class="['px-2.5 py-1 text-xs rounded font-medium', filterType === 'offline' ? 'bg-surface-0 dark:bg-surface-700 shadow text-slate-400' : 'text-muted-color']"
                            >
                                ⚪ Offline
                            </button>
                        </div>
                        <!-- Grid Density Toggle -->
                        <div class="flex items-center border border-surface-200 dark:border-surface-700 rounded-lg p-0.5 bg-surface-50 dark:bg-surface-800">
                            <button
                                @click="gridDensity = 'compact'"
                                :class="['px-2.5 py-1 text-xs rounded font-medium flex items-center gap-1 transition-all', gridDensity === 'compact' ? 'bg-surface-0 dark:bg-surface-700 shadow text-primary font-bold' : 'text-muted-color']"
                                v-tooltip.bottom="'Ukuran Kompak (-35% hemat ruang layar)'"
                            >
                                <i class="pi pi-table text-[10px]"></i>
                                Kompak (-35%)
                            </button>
                            <button
                                @click="gridDensity = 'normal'"
                                :class="['px-2.5 py-1 text-xs rounded font-medium flex items-center gap-1 transition-all', gridDensity === 'normal' ? 'bg-surface-0 dark:bg-surface-700 shadow text-primary font-bold' : 'text-muted-color']"
                                v-tooltip.bottom="'Ukuran Standar'"
                            >
                                <i class="pi pi-th-large text-[10px]"></i>
                                Normal
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <template #content>
                <!-- Color Legend -->
                <div class="flex flex-wrap items-center gap-4 py-3 text-xs text-muted-color border-b border-surface-100 dark:border-surface-800 mb-3">
                    <div class="flex items-center gap-1.5">
                        <span class="w-3.5 h-3.5 rounded bg-surface-0 dark:bg-surface-800 border border-surface-300 dark:border-surface-600 inline-block"></span>
                        <span>Bebas (Bersih)</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3.5 h-3.5 rounded bg-rose-500 border-2 border-rose-300 inline-block"></span>
                        <span>Terpakai & Online (🟢 Reply)</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3.5 h-3.5 rounded bg-rose-950/40 border border-dashed border-rose-500/50 inline-block"></span>
                        <span>Terpakai & Offline (⚪ RTO/Mati)</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3.5 h-3.5 rounded bg-amber-500 border border-amber-300 inline-block animate-pulse"></span>
                        <span class="font-bold text-amber-600 dark:text-amber-400">Host Liar / Belum Dicatat (⚠️ Reply)</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3.5 h-3.5 rounded bg-blue-600 border border-blue-400 inline-block"></span>
                        <span>Gateway / Router</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3.5 h-3.5 rounded bg-slate-300 dark:bg-slate-700 border border-slate-400 dark:border-slate-600 inline-block"></span>
                        <span>Network / Broadcast</span>
                    </div>
                    <div v-if="gridStats.last_scanned_at" class="ml-auto flex items-center gap-1 text-[11px] text-primary">
                        <i class="pi pi-check-circle"></i>
                        <span>Audit Terakhir: <b>{{ gridStats.last_scanned_at }}</b></span>
                    </div>
                </div>

                <!-- LOADING SPINNER -->
                <div v-if="loadingGrid" class="py-16 text-center text-muted-color">
                    <i class="pi pi-spin pi-spinner text-3xl text-primary mb-2"></i>
                    <p class="text-sm">Menyusun grid alokasi IP subnet...</p>
                </div>

                <!-- 256 CELL INTERACTIVE GRID (RESPONSIVE DENSITY) -->
                <div
                    v-else
                    :class="[
                        'grid rounded-xl border border-surface-200/80 dark:border-surface-800 transition-all duration-200',
                        gridDensity === 'compact'
                            ? 'grid-cols-10 sm:grid-cols-16 md:grid-cols-20 lg:grid-cols-24 gap-1 p-2 bg-surface-50/70 dark:bg-surface-950/50'
                            : 'grid-cols-8 sm:grid-cols-12 md:grid-cols-16 gap-1.5 p-3 bg-surface-50 dark:bg-surface-950/40'
                    ]"
                >
                    <div
                        v-for="cell in filteredCells"
                        :key="cell.ip"
                        @click="openCellDetail(cell)"
                        :class="[
                            'relative group cursor-pointer aspect-square flex flex-col items-center justify-center font-mono transition-all duration-150 select-none shadow-sm',
                            gridDensity === 'compact' ? 'rounded-md p-0.5' : 'rounded-lg p-1',
                            cell.type === 'available' ? 'bg-surface-0 dark:bg-surface-800 border border-surface-200 dark:border-surface-700/80 text-muted-color hover:border-emerald-500 hover:text-emerald-500 hover:scale-110 hover:shadow-md hover:z-10' : '',
                            cell.type === 'assigned' && (cell.live_status === 'online' || (cell.live_status === 'unverified' && cell.is_live)) ? 'bg-rose-500 text-white border-2 border-rose-300 dark:border-rose-400 font-bold shadow-sm hover:bg-rose-600 hover:scale-110 hover:shadow-md hover:z-10' : '',
                            cell.type === 'assigned' && cell.live_status === 'offline' ? 'bg-rose-950/30 dark:bg-rose-950/50 text-rose-300/80 border border-dashed border-rose-500/50 hover:bg-rose-900 hover:scale-110 hover:shadow-md hover:z-10' : '',
                            cell.type === 'assigned' && cell.live_status === 'unverified' && !cell.is_live ? 'bg-rose-600 text-white border border-rose-700 font-semibold hover:bg-rose-700 hover:scale-110 hover:shadow-md hover:z-10' : '',
                            cell.type === 'unregistered_active' ? 'bg-amber-500 text-surface-950 border-2 border-amber-300 font-black shadow-md animate-pulse hover:bg-amber-400 hover:scale-110 hover:shadow-lg hover:z-10' : '',
                            cell.type === 'gateway' ? 'bg-blue-600 text-white border border-blue-400 font-bold hover:bg-blue-500 hover:scale-110 hover:shadow-md hover:z-10' : '',
                            (cell.type === 'network' || cell.type === 'broadcast') ? 'bg-slate-300 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-400 dark:border-slate-700 opacity-70 cursor-not-allowed' : '',
                        ]"
                        v-tooltip.top="`${cell.ip} | ${cell.assignment ? cell.assignment.device + ' (' + (cell.assignment.kategori || 'Device') + ') [' + (cell.live_status === 'online' ? 'ONLINE' : cell.live_status === 'offline' ? 'OFFLINE' : 'STATUS TERDAFTAR') + ']' : (cell.type === 'unregistered_active' ? '⚠️ AKTIF DI JARINGAN (BELUM TERDAFTAR)' : cell.type === 'gateway' ? 'DEFAULT GATEWAY' : cell.type === 'network' ? 'NETWORK ADDRESS' : cell.type === 'broadcast' ? 'BROADCAST ADDRESS' : 'TERSEDIA (BEBAS / OFFLINE)')}`"
                    >
                        <span :class="gridDensity === 'compact' ? 'text-[9.5px] leading-none font-bold' : 'text-[11px] leading-none font-semibold'">.{{ cell.host }}</span>

                        <!-- Online pulsing indicator -->
                        <span
                            v-if="cell.live_status === 'online' || (cell.live_status !== 'offline' && cell.is_live)"
                            :class="gridDensity === 'compact' ? 'top-0.5 right-0.5 w-1.5 h-1.5' : 'top-1 right-1 w-2 h-2'"
                            class="absolute rounded-full bg-emerald-400 animate-ping"
                        ></span>
                        <span
                            v-if="cell.live_status === 'online' || (cell.live_status !== 'offline' && cell.is_live)"
                            :class="gridDensity === 'compact' ? 'top-0.5 right-0.5 w-1.5 h-1.5' : 'top-1 right-1 w-2 h-2'"
                            class="absolute rounded-full bg-emerald-500"
                        ></span>

                        <!-- Offline badge -->
                        <span
                            v-if="cell.type === 'assigned' && cell.live_status === 'offline'"
                            :class="gridDensity === 'compact' ? 'top-0.5 right-0.5 w-1 h-1' : 'top-1 right-1 w-1.5 h-1.5'"
                            class="absolute rounded-full bg-slate-400"
                        ></span>

                        <!-- Text badges -->
                        <span v-if="cell.type === 'unregistered_active'" :class="gridDensity === 'compact' ? 'text-[6.5px] mt-0.5 px-0.5' : 'text-[8px] mt-0.5 px-1'" class="tracking-tighter uppercase font-black bg-surface-950 text-amber-300 rounded leading-none">! AKTIF</span>
                        <span v-else-if="cell.type === 'gateway'" :class="gridDensity === 'compact' ? 'text-[7px] mt-0.5' : 'text-[9px] mt-0.5'" class="opacity-90 font-black leading-none">GW</span>
                        <span v-else-if="cell.type === 'network'" :class="gridDensity === 'compact' ? 'text-[6.5px] mt-0.5' : 'text-[8px] mt-0.5'" class="opacity-70 uppercase font-bold leading-none">NET</span>
                        <span v-else-if="cell.type === 'broadcast'" :class="gridDensity === 'compact' ? 'text-[6.5px] mt-0.5' : 'text-[8px] mt-0.5'" class="opacity-70 uppercase font-bold leading-none">BC</span>
                        <span v-else-if="cell.type === 'assigned' && cell.live_status === 'offline'" :class="gridDensity === 'compact' ? 'text-[6.5px] mt-0.5' : 'text-[8px] mt-0.5'" class="opacity-70 uppercase font-medium leading-none">OFF</span>
                        <span v-else-if="cell.type === 'assigned' && cell.live_status === 'online'" :class="gridDensity === 'compact' ? 'text-[6.5px] mt-0.5' : 'text-[8px] mt-0.5'" class="opacity-90 uppercase font-bold leading-none">ON</span>
                    </div>
                </div>

                <div v-if="!loadingGrid && filteredCells.length === 0" class="py-12 text-center text-muted-color">
                    <i class="pi pi-filter-slash text-2xl mb-1"></i>
                    <p class="text-sm">Tidak ada IP yang cocok dengan filter / pencarian.</p>
                </div>
            </template>
        </Card>

        <!-- TABLE: ALOKASI TERDAFTAR DI SUBNET INI -->
        <Card class="shadow-sm border border-surface-200 dark:border-surface-700">
            <template #title>
                <div class="flex items-center justify-between">
                    <span class="text-lg font-bold text-surface-900 dark:text-surface-0 flex items-center gap-2">
                        <i class="pi pi-list text-primary"></i>
                        Daftar Host & Perangkat Terdaftar ({{ assignedTableData.length }} Perangkat)
                    </span>
                    <IconField>
                        <InputIcon>
                            <i class="pi pi-search" />
                        </InputIcon>
                        <InputText
                            v-model="ipTableFilters['global'].value"
                            placeholder="Cari dalam tabel..."
                            class="p-inputtext-sm text-xs"
                        />
                    </IconField>
                </div>
            </template>
            <template #content>
                <DataTable
                    :value="assignedTableData"
                    v-model:filters="ipTableFilters"
                    :globalFilterFields="['ip', 'device', 'kategori', 'mac_address', 'status', 'keterangan']"
                    paginator
                    :rows="10"
                    :rowsPerPageOptions="[5, 10, 20, 50]"
                    class="p-datatable-sm"
                    responsiveLayout="scroll"
                >
                    <template #empty>Belum ada IP yang dialokasikan pada subnet ini.</template>

                    <Column field="ip" header="IP Address" sortable style="width: 15%">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-bold text-primary">{{ data.ip }}</span>
                                <span
                                    v-if="data.is_gateway"
                                    class="px-1.5 py-0.5 text-[9px] font-bold rounded bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300"
                                >
                                    GW
                                </span>
                                <span
                                    v-if="data.is_live"
                                    class="px-1.5 py-0.5 text-[9px] font-bold rounded bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300"
                                >
                                    LIVE
                                </span>
                            </div>
                        </template>
                    </Column>

                    <Column field="device" header="Nama Perangkat / Host" sortable style="width: 20%">
                        <template #body="{ data }">
                            <span class="font-semibold text-surface-900 dark:text-surface-0">{{ data.device }}</span>
                        </template>
                    </Column>

                    <Column field="kategori" header="Kategori" sortable style="width: 14%">
                        <template #body="{ data }">
                            <Tag :value="data.kategori" severity="secondary" rounded />
                        </template>
                    </Column>

                    <Column field="status" header="Status" sortable style="width: 10%">
                        <template #body="{ data }">
                            <Tag
                                :value="data.status || 'Static'"
                                :severity="data.status === 'DHCP' ? 'success' : 'info'"
                            />
                        </template>
                    </Column>

                    <Column field="mac_address" header="MAC Address" style="width: 15%">
                        <template #body="{ data }">
                            <code class="text-xs text-muted-color">{{ data.mac_address || '-' }}</code>
                        </template>
                    </Column>

                    <Column field="source" header="Sumber" style="width: 12%">
                        <template #body="{ data }">
                            <span class="text-xs text-muted-color capitalize">
                                {{ data.source === 'manual' ? 'Manual Input' : data.source?.replace('_', ' ') }}
                            </span>
                        </template>
                    </Column>

                    <Column header="Aksi" style="width: 14%">
                        <template #body="{ data }">
                            <div class="flex items-center gap-1.5">
                                <Button
                                    icon="pi pi-arrow-right-arrow-left"
                                    severity="secondary"
                                    text
                                    rounded
                                    @click="triggerPing(data.ip)"
                                    v-tooltip.bottom="'Ping Test'"
                                />
                                <Button
                                    icon="pi pi-pencil"
                                    severity="info"
                                    text
                                    rounded
                                    @click="openCellDetail({ ip: data.ip, host: data.host, type: 'assigned', assignment: data.assignment, is_live: data.is_live })"
                                    v-tooltip.bottom="'Edit Alokasi'"
                                />
                                <Button
                                    icon="pi pi-trash"
                                    severity="danger"
                                    text
                                    rounded
                                    @click="releaseIp(data.ip)"
                                    v-tooltip.bottom="'Lepaskan IP'"
                                />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </template>
        </Card>
    </div>

    <!-- ==================================================== -->
    <!-- TAB 2: MIKROTIK REAL-TIME TRAFFIC & BANDWIDTH USAGE  -->
    <!-- ==================================================== -->
    <div v-show="activeTab === 'router'" class="space-y-6">

        <!-- TOTAL AGGREGATE BANDWIDTH CONSUMPTION CARDS -->
        <div class="p-4 bg-surface-50 dark:bg-surface-900/40 rounded-2xl border border-surface-200/80 dark:border-surface-700">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
                <div class="flex items-center gap-2.5">
                    <span class="p-2 rounded-xl bg-primary/10 text-primary">
                        <i class="pi pi-chart-pie text-lg"></i>
                    </span>
                    <div>
                        <h3 class="font-bold text-sm text-surface-900 dark:text-surface-0">Total Konsumsi Seluruh Router (Agregat)</h3>
                        <p class="text-xs text-muted-color">Akumulasi pemakaian bandwidth gabungan dari semua router MikroTik yang aktif.</p>
                    </div>
                </div>
                <Button
                    label="Kelola Router"
                    icon="pi pi-cog"
                    severity="secondary"
                    outlined
                    class="p-button-sm text-xs"
                    @click="routerManagerDialog = true"
                />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <!-- Hari Ini (Today Total) -->
                <div class="p-4 bg-surface-0 dark:bg-surface-800 rounded-xl border border-surface-200 dark:border-surface-700/60 shadow-xs flex flex-col justify-between transition-colors">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-muted-color">Total Hari Ini</span>
                        <span class="p-1.5 rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400 text-sm">
                            <i class="pi pi-calendar"></i>
                        </span>
                    </div>
                    <h3 class="text-2xl font-black text-surface-900 dark:text-surface-0">
                        {{ totalConsumption.today.formatted }}
                    </h3>
                    <div class="mt-3 pt-2 border-t border-surface-100 dark:border-surface-700/60 flex items-center justify-between text-xs">
                        <span class="text-cyan-600 dark:text-cyan-400 font-medium">⬇️ Rx: {{ totalConsumption.today.rx_fmt }}</span>
                        <span class="text-pink-600 dark:text-pink-400 font-medium">⬆️ Tx: {{ totalConsumption.today.tx_fmt }}</span>
                    </div>
                </div>

                <!-- Mingguan (Weekly Total) -->
                <div class="p-4 bg-surface-0 dark:bg-surface-800 rounded-xl border border-surface-200 dark:border-surface-700/60 shadow-xs flex flex-col justify-between transition-colors">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-muted-color">Total Minggu Ini (7 Hari)</span>
                        <span class="p-1.5 rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400 text-sm">
                            <i class="pi pi-chart-bar"></i>
                        </span>
                    </div>
                    <h3 class="text-2xl font-black text-surface-900 dark:text-surface-0">
                        {{ totalConsumption.weekly.formatted }}
                    </h3>
                    <div class="mt-3 pt-2 border-t border-surface-100 dark:border-surface-700/60 flex items-center justify-between text-xs">
                        <span class="text-cyan-600 dark:text-cyan-400 font-medium">⬇️ Rx: {{ totalConsumption.weekly.rx_fmt }}</span>
                        <span class="text-pink-600 dark:text-pink-400 font-medium">⬆️ Tx: {{ totalConsumption.weekly.tx_fmt }}</span>
                    </div>
                </div>

                <!-- Bulanan (Monthly Total) -->
                <div class="p-4 bg-surface-0 dark:bg-surface-800 rounded-xl border border-surface-200 dark:border-surface-700/60 shadow-xs flex flex-col justify-between transition-colors">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-muted-color">Total Bulan Ini (30 Hari)</span>
                        <span class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400 text-sm">
                            <i class="pi pi-database"></i>
                        </span>
                    </div>
                    <h3 class="text-2xl font-black text-surface-900 dark:text-surface-0">
                        {{ totalConsumption.monthly.formatted }}
                    </h3>
                    <div class="mt-3 pt-2 border-t border-surface-100 dark:border-surface-700/60 flex items-center justify-between text-xs">
                        <span class="text-cyan-600 dark:text-cyan-400 font-medium">⬇️ Rx: {{ totalConsumption.monthly.rx_fmt }}</span>
                        <span class="text-pink-600 dark:text-pink-400 font-medium">⬆️ Tx: {{ totalConsumption.monthly.tx_fmt }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- REAL-TIME TRAFFIC & PER-ROUTER MONITORING CARDS -->
        <div class="grid grid-cols-1 gap-6">
            <Card v-for="(list, l) in lists" :key="l" class="shadow-sm border border-surface-200 dark:border-surface-700">
                <template #content>
                    <Panel toggleable>
                        <template #header>
                            <div class="flex items-center gap-3">
                                <span class="text-xl font-bold text-surface-900 dark:text-surface-0" v-if="list && list.name">{{ list.name }}</span>
                                <span class="text-sm font-mono text-muted-color" v-if="list && list.host">({{ list.host }}:{{ list.port }})</span>
                                <Tag severity="success" value="CONNECTED" rounded v-if="list"></Tag>
                                <Tag severity="danger" value="DISCONNECTED" rounded v-if="!list"></Tag>
                            </div>
                        </template>

                        <!-- System Resources Grid -->
                        <div v-if="list" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3 p-4 bg-surface-50 dark:bg-surface-900/50 rounded-xl mb-4 border border-surface-200/80 dark:border-surface-700">
                            <div>
                                <span class="text-[11px] uppercase tracking-wider text-muted-color">Uptime</span>
                                <p class="font-semibold text-xs text-surface-900 dark:text-surface-0 mt-0.5">{{ getRouterState(list.id)?.uptime || '-' }}</p>
                            </div>
                            <div>
                                <span class="text-[11px] uppercase tracking-wider text-muted-color">CPU Load</span>
                                <p class="font-semibold text-xs text-surface-900 dark:text-surface-0 mt-0.5">{{ getRouterState(list.id)?.cpu ?? 0 }}%</p>
                            </div>
                            <div>
                                <span class="text-[11px] uppercase tracking-wider text-muted-color">Free RAM</span>
                                <p class="font-semibold text-xs text-surface-900 dark:text-surface-0 mt-0.5">{{ getRouterState(list.id)?.memory || '-' }}</p>
                            </div>
                            <div>
                                <span class="text-[11px] uppercase tracking-wider text-muted-color">Free HDD</span>
                                <p class="font-semibold text-xs text-surface-900 dark:text-surface-0 mt-0.5">{{ getRouterState(list.id)?.disk || '-' }}</p>
                            </div>
                            <div>
                                <span class="text-[11px] uppercase tracking-wider text-muted-color">Total IP Host</span>
                                <p class="font-semibold text-xs text-surface-900 dark:text-surface-0 mt-0.5">{{ list.address?.length || 0 }} IP</p>
                            </div>
                            <div>
                                <Button
                                    type="button"
                                    label="Daftar IP"
                                    icon="pi pi-eye"
                                    severity="info"
                                    text
                                    class="p-button-sm text-xs mt-1"
                                    @click="showNetwork(list.address, list.name)"
                                />
                            </div>
                        </div>

                        <!-- KONSUMSI BANDWIDTH TIAP ROUTER (Hari Ini, Minggu Ini, Bulan Ini) -->
                        <div v-if="list" class="mb-4">
                            <div class="flex items-center gap-2 mb-2">
                                <i class="pi pi-chart-line text-xs text-primary"></i>
                                <span class="text-xs font-bold uppercase tracking-wider text-surface-900 dark:text-surface-0">
                                    Konsumsi Bandwidth: {{ list.name }}
                                </span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <!-- Hari Ini Router Ini -->
                                <div class="p-3.5 bg-surface-50/80 dark:bg-surface-900/60 rounded-xl border border-surface-200/80 dark:border-surface-700/80 shadow-xs flex flex-col justify-between">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-[11px] font-semibold uppercase tracking-wider text-muted-color">Hari Ini</span>
                                        <span class="p-1 rounded bg-blue-50 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400 text-xs">
                                            <i class="pi pi-calendar"></i>
                                        </span>
                                    </div>
                                    <div class="text-2xl font-black text-surface-900 dark:text-surface-0">
                                        {{ getRouterState(list.id)?.consumption?.today?.formatted || '0 B' }}
                                    </div>
                                    <div class="mt-2.5 pt-2 border-t border-surface-200/60 dark:border-surface-700/60 flex items-center justify-between text-[11px]">
                                        <span class="text-cyan-600 dark:text-cyan-400 font-medium">⬇️ Rx: {{ getRouterState(list.id)?.consumption?.today?.rx_fmt || '0 B' }}</span>
                                        <span class="text-pink-600 dark:text-pink-400 font-medium">⬆️ Tx: {{ getRouterState(list.id)?.consumption?.today?.tx_fmt || '0 B' }}</span>
                                    </div>
                                </div>

                                <!-- Mingguan Router Ini -->
                                <div class="p-3.5 bg-surface-50/80 dark:bg-surface-900/60 rounded-xl border border-surface-200/80 dark:border-surface-700/80 shadow-xs flex flex-col justify-between">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-[11px] font-semibold uppercase tracking-wider text-muted-color">Minggu Ini (7 Hari)</span>
                                        <span class="p-1 rounded bg-purple-50 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400 text-xs">
                                            <i class="pi pi-chart-bar"></i>
                                        </span>
                                    </div>
                                    <div class="text-2xl font-black text-surface-900 dark:text-surface-0">
                                        {{ getRouterState(list.id)?.consumption?.weekly?.formatted || '0 B' }}
                                    </div>
                                    <div class="mt-2.5 pt-2 border-t border-surface-200/60 dark:border-surface-700/60 flex items-center justify-between text-[11px]">
                                        <span class="text-cyan-600 dark:text-cyan-400 font-medium">⬇️ Rx: {{ getRouterState(list.id)?.consumption?.weekly?.rx_fmt || '0 B' }}</span>
                                        <span class="text-pink-600 dark:text-pink-400 font-medium">⬆️ Tx: {{ getRouterState(list.id)?.consumption?.weekly?.tx_fmt || '0 B' }}</span>
                                    </div>
                                </div>

                                <!-- Bulanan Router Ini -->
                                <div class="p-3.5 bg-surface-50/80 dark:bg-surface-900/60 rounded-xl border border-surface-200/80 dark:border-surface-700/80 shadow-xs flex flex-col justify-between">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-[11px] font-semibold uppercase tracking-wider text-muted-color">Bulan Ini (30 Hari)</span>
                                        <span class="p-1 rounded bg-emerald-50 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400 text-xs">
                                            <i class="pi pi-database"></i>
                                        </span>
                                    </div>
                                    <div class="text-2xl font-black text-surface-900 dark:text-surface-0">
                                        {{ getRouterState(list.id)?.consumption?.monthly?.formatted || '0 B' }}
                                    </div>
                                    <div class="mt-2.5 pt-2 border-t border-surface-200/60 dark:border-surface-700/60 flex items-center justify-between text-[11px]">
                                        <span class="text-cyan-600 dark:text-cyan-400 font-medium">⬇️ Rx: {{ getRouterState(list.id)?.consumption?.monthly?.rx_fmt || '0 B' }}</span>
                                        <span class="text-pink-600 dark:text-pink-400 font-medium">⬆️ Tx: {{ getRouterState(list.id)?.consumption?.monthly?.tx_fmt || '0 B' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Real-time Live Bandwidth Speed Gauges & Interface Selection -->
                        <div v-if="list" class="flex flex-col sm:flex-row items-center justify-between gap-4 p-4 bg-surface-50 dark:bg-surface-900/40 rounded-xl mb-4 border border-surface-200/80 dark:border-surface-700">
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-semibold text-surface-900 dark:text-surface-0">Pilih Interface:</span>
                                <SplitButton
                                    :label="getRouterState(list.id)?.selectedInterface || 'Pilih Port'"
                                    :model="getRouterState(list.id)?.menuItems || []"
                                    text
                                    class="p-button-sm font-semibold"
                                />
                                <Tag
                                    :value="isInterfaceRunning(list.id) ? 'LINK UP (AKTIF)' : 'NO CARRIER'"
                                    :severity="isInterfaceRunning(list.id) ? 'success' : 'secondary'"
                                    rounded
                                />
                            </div>

                            <div class="flex items-center gap-6">
                                <!-- Downstream -->
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-cyan-100 text-cyan-600 dark:bg-cyan-500/20 dark:text-cyan-400 flex items-center justify-center text-lg">
                                        <i class="pi pi-arrow-down"></i>
                                    </div>
                                    <div>
                                        <span class="text-[11px] uppercase tracking-wider font-semibold text-cyan-600 dark:text-cyan-400">Downstream (Rx)</span>
                                        <h4 class="text-xl font-black text-surface-900 dark:text-surface-0">{{ getRouterState(list.id)?.rxFmt || '0 B/s' }}</h4>
                                    </div>
                                </div>

                                <!-- Upstream -->
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-pink-100 text-pink-600 dark:bg-pink-500/20 dark:text-pink-400 flex items-center justify-center text-lg">
                                        <i class="pi pi-arrow-up"></i>
                                    </div>
                                    <div>
                                        <span class="text-[11px] uppercase tracking-wider font-semibold text-pink-600 dark:text-pink-400">Upstream (Tx)</span>
                                        <h4 class="text-xl font-black text-surface-900 dark:text-surface-0">{{ getRouterState(list.id)?.txFmt || '0 B/s' }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Real-Time ECharts Live Area Graph -->
                        <div v-if="list" class="w-full">
                            <div class="echart-container bg-surface-0 dark:bg-surface-800 border border-surface-200 dark:border-surface-700">
                                <div :ref="el => setChartRef(el, l)" class="chart" style="width: 100%; height: 420px; min-height: 320px;"></div>
                            </div>
                        </div>

                        <div v-if="!list" class="py-6 text-center">
                            <Tag severity="danger" value="OFFLINE / GAGAL TERKONEKSI" rounded></Tag>
                            <p class="text-sm text-muted-color mt-2">Pastikan router MikroTik menyala, IP dapat di-ping, dan port API (8728) aktif.</p>
                            <Button label="Kelola & Periksa Kredensial" icon="pi pi-cog" class="mt-3" @click="routerManagerDialog = true" />
                        </div>
                    </Panel>
                </template>
            </Card>
        </div>
    </div>

    <!-- ==================================================== -->
    <!-- DIALOG: KELOLA ROUTER MIKROTIK (CRUD)                -->
    <!-- ==================================================== -->
    <Dialog
        v-model:visible="routerManagerDialog"
        modal
        header="Manajemen Router MikroTik"
        :style="{ width: '60rem' }"
        :breakpoints="{ '960px': '75vw', '640px': '95vw' }"
    >
        <div class="space-y-4 pt-2">
            <div class="flex items-center justify-between pb-3 border-b border-surface-200 dark:border-surface-700">
                <p class="text-xs text-muted-color">
                    Kredensial username dan password disimpan menggunakan <b>Enkripsi AES-256 Laravel</b>.
                </p>
                <Button label="Tambah Router" icon="pi pi-plus" severity="primary" @click="openAddRouter" />
            </div>

            <DataTable :value="routerList" responsiveLayout="scroll" class="p-datatable-sm">
                <template #empty>Belum ada router yang didaftarkan.</template>

                <Column field="name" header="Nama Router" style="width: 25%">
                    <template #body="{ data }">
                        <div>
                            <span class="font-bold text-surface-900 dark:text-surface-0">{{ data.name }}</span>
                            <p class="text-[11px] text-muted-color">{{ data.keterangan || '-' }}</p>
                        </div>
                    </template>
                </Column>

                <Column field="host" header="Host / IP" style="width: 22%">
                    <template #body="{ data }">
                        <code class="text-xs font-mono text-primary">{{ data.host }}:{{ data.port }}</code>
                    </template>
                </Column>

                <Column field="user" header="User API" style="width: 15%">
                    <template #body="{ data }">
                        <span class="text-xs font-mono">{{ data.user }}</span>
                    </template>
                </Column>

                <Column field="is_active" header="Status" style="width: 15%">
                    <template #body="{ data }">
                        <Tag
                            :value="data.is_active ? 'Aktif' : 'Nonaktif'"
                            :severity="data.is_active ? 'success' : 'danger'"
                            class="cursor-pointer"
                            @click="toggleRouter(data)"
                            v-tooltip.bottom="'Klik untuk ubah status'"
                        />
                    </template>
                </Column>

                <Column header="Aksi" style="width: 23%">
                    <template #body="{ data }">
                        <div class="flex items-center gap-1.5">
                            <Button
                                icon="pi pi-pencil"
                                severity="info"
                                text
                                rounded
                                @click="openEditRouter(data)"
                                v-tooltip.bottom="'Ubah Router'"
                            />
                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                text
                                rounded
                                @click="deleteRouter(data)"
                                v-tooltip.bottom="'Hapus Router'"
                            />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </Dialog>

    <!-- ==================================================== -->
    <!-- DIALOG: FORM TAMBAH / UBAH ROUTER & TEST CONNECTION  -->
    <!-- ==================================================== -->
    <Dialog
        v-model:visible="routerFormDialog"
        modal
        :header="isEditingRouter ? 'Ubah Router MikroTik' : 'Tambah Router MikroTik Baru'"
        :style="{ width: '32rem' }"
        :breakpoints="{ '640px': '90vw' }"
    >
        <div class="space-y-4 pt-2">
            <div>
                <label class="text-xs font-semibold text-muted-color">Nama Router *</label>
                <InputText
                    v-model="formRouter.name"
                    placeholder="Contoh: Router Core Kantor Diskominfo"
                    class="w-full mt-1"
                />
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div class="col-span-2">
                    <label class="text-xs font-semibold text-muted-color">IP / Host Router *</label>
                    <InputText
                        v-model="formRouter.host"
                        placeholder="Contoh: 192.168.1.1 atau 10.20.0.1"
                        class="w-full mt-1 font-mono text-xs"
                    />
                </div>
                <div>
                    <label class="text-xs font-semibold text-muted-color">Port API *</label>
                    <InputText
                        v-model="formRouter.port"
                        placeholder="8728"
                        class="w-full mt-1 font-mono text-xs"
                    />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-semibold text-muted-color">Username API *</label>
                    <InputText
                        v-model="formRouter.user"
                        placeholder="admin / bookkeeper"
                        class="w-full mt-1 font-mono text-xs"
                    />
                </div>
                <div>
                    <label class="text-xs font-semibold text-muted-color">
                        Password API {{ isEditingRouter ? '(Opsional)' : '*' }}
                    </label>
                    <InputText
                        v-model="formRouter.pass"
                        type="password"
                        :placeholder="isEditingRouter ? 'Kosongkan jika tak diubah' : 'Password API'"
                        class="w-full mt-1 font-mono text-xs"
                    />
                </div>
            </div>

            <div>
                <label class="text-xs font-semibold text-muted-color">Keterangan / Lokasi</label>
                <InputText
                    v-model="formRouter.keterangan"
                    placeholder="Contoh: Gateway Internet Gedung A Lt. 1"
                    class="w-full mt-1 text-xs"
                />
            </div>

            <!-- Test Connection Result Box -->
            <div v-if="connectionTestResult" :class="[
                'p-3 rounded-xl border text-xs',
                connectionTestResult.success
                    ? 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-300 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300'
                    : 'bg-rose-50 dark:bg-rose-950/30 border-rose-300 dark:border-rose-800 text-rose-800 dark:text-rose-300'
            ]">
                <div class="font-bold mb-1 flex items-center gap-1.5">
                    <i :class="connectionTestResult.success ? 'pi pi-check-circle' : 'pi pi-times-circle'"></i>
                    {{ connectionTestResult.message }}
                </div>
                <div v-if="connectionTestResult.success" class="grid grid-cols-2 gap-1 text-[11px] mt-2 opacity-90">
                    <div>Board: <b>{{ connectionTestResult.board_name }}</b></div>
                    <div>Version: <b>{{ connectionTestResult.version }}</b></div>
                    <div>CPU: <b>{{ connectionTestResult.cpu_load }}</b></div>
                    <div>Free RAM: <b>{{ connectionTestResult.free_memory }}</b></div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-4 border-t border-surface-200 dark:border-surface-700">
                <Button
                    label="Uji Koneksi"
                    icon="pi pi-link"
                    severity="info"
                    outlined
                    :loading="testingConnection"
                    @click="testRouterConnection"
                />
                <div class="flex items-center gap-2">
                    <Button label="Batal" severity="secondary" text @click="routerFormDialog = false" />
                    <Button
                        label="Simpan Router"
                        icon="pi pi-check"
                        severity="primary"
                        :loading="savingRouter"
                        @click="saveRouter"
                    />
                </div>
            </div>
        </div>
    </Dialog>

    <!-- ==================================================== -->
    <!-- DIALOG: DETAIL & ALOKASI IP                          -->
    <!-- ==================================================== -->
    <Dialog
        v-model:visible="ipDetailDialog"
        modal
        :header="`Detail Host: ${selectedCell?.ip}`"
        :style="{ width: '32rem' }"
        :breakpoints="{ '640px': '90vw' }"
    >
        <div v-if="selectedCell" class="space-y-4 pt-2">
            <div class="flex items-center justify-between p-3 rounded-xl bg-surface-100 dark:bg-surface-800">
                <div>
                    <span class="text-xs text-muted-color">Host IP:</span>
                    <h4 class="font-mono text-lg font-bold text-surface-900 dark:text-surface-0">{{ selectedCell.ip }}</h4>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Tag
                        v-if="selectedCell.live_status && selectedCell.live_status !== 'unverified'"
                        :value="selectedCell.live_status === 'online' ? '🟢 ONLINE (REPLY)' : '⚪ OFFLINE (RTO)'"
                        :severity="selectedCell.live_status === 'online' ? 'success' : 'secondary'"
                    />
                    <Tag
                        :value="selectedCell.type === 'unregistered_active' ? '⚠️ AKTIF (BELUM DICATAT)' : selectedCell.type.toUpperCase()"
                        :severity="
                            selectedCell.type === 'available' ? 'success' :
                            selectedCell.type === 'assigned' ? 'danger' :
                            selectedCell.type === 'unregistered_active' ? 'warn' :
                            selectedCell.type === 'gateway' ? 'info' : 'secondary'
                        "
                    />
                </div>
            </div>

            <!-- Contextual Status Alert Banners -->
            <div
                v-if="selectedCell.type === 'unregistered_active'"
                class="p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-700/60 rounded-xl flex items-start gap-3 text-xs text-amber-900 dark:text-amber-200"
            >
                <i class="pi pi-exclamation-triangle text-base text-amber-600 dark:text-amber-400 mt-0.5"></i>
                <div>
                    <span class="font-bold">Host Liar / Aktif Ditemukan:</span>
                    <p class="mt-0.5 leading-relaxed">
                        IP ini aktif merespon ICMP ping saat audit live subnet, namun belum tercatat di database IPAM. Anda dapat langsung melengkapi data formulir di bawah untuk mendaftarkannya secara resmi.
                    </p>
                </div>
            </div>

            <div
                v-else-if="selectedCell.type === 'assigned' && selectedCell.live_status === 'offline'"
                class="p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-700/60 rounded-xl flex items-start gap-3 text-xs text-rose-900 dark:text-rose-200"
            >
                <i class="pi pi-info-circle text-base text-rose-600 dark:text-rose-400 mt-0.5"></i>
                <div>
                    <span class="font-bold">Perangkat Terdaftar Tidak Merespon (Offline / RTO):</span>
                    <p class="mt-0.5 leading-relaxed">
                        IP ini dialokasikan ke <b>{{ selectedCell.assignment?.device }}</b>, namun tidak membalas ICMP ping saat audit terakhir. Perangkat mungkin sedang mati, terputus, atau firewall-nya memblokir ICMP.
                    </p>
                </div>
            </div>

            <div
                v-else-if="selectedCell.type === 'assigned' && (selectedCell.live_status === 'online' || selectedCell.is_live)"
                class="p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-700/60 rounded-xl flex items-start gap-3 text-xs text-emerald-900 dark:text-emerald-200"
            >
                <i class="pi pi-check-circle text-base text-emerald-600 dark:text-emerald-400 mt-0.5"></i>
                <div>
                    <span class="font-bold">Perangkat Terdaftar & Aktif (Online):</span>
                    <p class="mt-0.5 leading-relaxed">
                        IP ini dialokasikan ke <b>{{ selectedCell.assignment?.device }}</b> dan merespon ICMP ping secara normal di jaringan fisik.
                    </p>
                </div>
            </div>

            <div
                v-else-if="selectedCell.type === 'available'"
                class="p-3 bg-surface-100 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 rounded-xl flex items-start gap-3 text-xs text-surface-700 dark:text-surface-300"
            >
                <i class="pi pi-shield text-base text-emerald-600 dark:text-emerald-400 mt-0.5"></i>
                <div>
                    <span class="font-bold">IP Tersedia (Bebas / Bersih):</span>
                    <p class="mt-0.5 leading-relaxed">
                        IP ini belum dialokasikan dan tidak ada perangkat yang merespon pada alamat ini. Siap digunakan untuk perangkat baru.
                    </p>
                </div>
            </div>

            <div v-if="selectedCell.assignment && !isEditing" class="space-y-3">
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <span class="text-xs text-muted-color">Perangkat:</span>
                        <p class="font-semibold text-surface-900 dark:text-surface-0">{{ selectedCell.assignment.device }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-muted-color">Kategori:</span>
                        <p class="font-semibold text-surface-900 dark:text-surface-0">{{ selectedCell.assignment.kategori }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-muted-color">Status Alokasi:</span>
                        <p class="font-semibold text-surface-900 dark:text-surface-0">{{ selectedCell.assignment.status || 'Static' }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-muted-color">MAC Address:</span>
                        <p class="font-mono text-xs text-surface-900 dark:text-surface-0">{{ selectedCell.assignment.mac_address || '-' }}</p>
                    </div>
                    <div class="col-span-2">
                        <span class="text-xs text-muted-color">Keterangan / Lokasi:</span>
                        <p class="text-surface-700 dark:text-surface-300">{{ selectedCell.assignment.keterangan || '-' }}</p>
                    </div>
                    <div class="col-span-2">
                        <span class="text-xs text-muted-color">Sumber / Terakhir Terlihat:</span>
                        <p class="text-xs text-muted-color">
                            {{ selectedCell.assignment.source || 'manual' }}
                            <span v-if="selectedCell.assignment.last_seen"> ({{ selectedCell.assignment.last_seen }})</span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-surface-200 dark:border-surface-700">
                    <Button
                        label="Ping Test"
                        icon="pi pi-arrow-right-arrow-left"
                        severity="secondary"
                        @click="triggerPing(selectedCell.ip)"
                    />
                    <div class="flex items-center gap-2">
                        <Button
                            label="Ubah"
                            icon="pi pi-pencil"
                            severity="info"
                            outlined
                            @click="isEditing = true"
                        />
                        <Button
                            label="Lepaskan IP"
                            icon="pi pi-trash"
                            severity="danger"
                            @click="releaseIp(selectedCell.ip)"
                        />
                    </div>
                </div>
            </div>

            <div v-else-if="selectedCell.type !== 'network' && selectedCell.type !== 'broadcast'" class="space-y-3">
                <div>
                    <label class="text-xs font-semibold text-muted-color">Nama Perangkat / Host *</label>
                    <InputText
                        v-model="formAssignment.device"
                        placeholder="Contoh: PC-Staff-01, AP-Lantai2"
                        class="w-full mt-1"
                    />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-muted-color">Kategori</label>
                        <Select
                            v-model="formAssignment.kategori"
                            :options="categoryOptions"
                            class="w-full mt-1"
                        />
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-muted-color">Status Alokasi</label>
                        <Select
                            v-model="formAssignment.status"
                            :options="statusOptions"
                            class="w-full mt-1"
                        />
                    </div>
                </div>

                <div>
                    <label class="text-xs font-semibold text-muted-color">MAC Address (Opsional)</label>
                    <InputText
                        v-model="formAssignment.mac_address"
                        placeholder="00:11:22:33:44:55"
                        class="w-full mt-1 font-mono text-xs"
                    />
                </div>

                <div>
                    <label class="text-xs font-semibold text-muted-color">Keterangan / Lokasi</label>
                    <InputText
                        v-model="formAssignment.keterangan"
                        placeholder="Contoh: Ruang Rapat Lt. 2"
                        class="w-full mt-1"
                    />
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-surface-200 dark:border-surface-700">
                    <Button
                        label="Ping Test"
                        icon="pi pi-arrow-right-arrow-left"
                        severity="secondary"
                        text
                        @click="triggerPing(selectedCell.ip)"
                    />
                    <div class="flex items-center gap-2">
                        <Button
                            v-if="selectedCell.assignment"
                            label="Batal"
                            severity="secondary"
                            text
                            @click="isEditing = false"
                        />
                        <Button
                            label="Simpan Alokasi"
                            icon="pi pi-check"
                            severity="primary"
                            :loading="savingAssignment"
                            @click="saveAssignment"
                        />
                    </div>
                </div>
            </div>

            <div v-else class="p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-lg text-xs text-amber-800 dark:text-amber-300">
                IP ini adalah alamat <b>{{ selectedCell.type.toUpperCase() }}</b> subnet dan tidak dapat dialokasikan ke perangkat individual.
            </div>
        </div>
    </Dialog>

    <!-- ==================================================== -->
    <!-- DIALOG: TAMBAH SUBNET BARU                           -->
    <!-- ==================================================== -->
    <Dialog
        v-model:visible="newSubnetDialog"
        modal
        header="Tambah Subnet Jaringan Baru"
        :style="{ width: '28rem' }"
        :breakpoints="{ '640px': '90vw' }"
    >
        <div class="space-y-4 pt-2">
            <div>
                <label class="text-xs font-semibold text-muted-color">Alamat Network IP *</label>
                <InputText
                    v-model="formSubnet.network_ip"
                    placeholder="Contoh: 192.168.2.0 atau 10.20.10.0"
                    class="w-full mt-1 font-mono text-sm"
                />
            </div>

            <div>
                <label class="text-xs font-semibold text-muted-color">Prefix CIDR / Netmask *</label>
                <Select
                    v-model="formSubnet.cidr"
                    :options="cidrOptions"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full mt-1"
                />
            </div>

            <!-- Kartu Pratinjau Kalkulasi Subnet Otomatis -->
            <div class="p-3 rounded-xl border border-blue-200 dark:border-blue-900/60 bg-blue-50/70 dark:bg-blue-950/30 text-xs text-surface-700 dark:text-surface-300 space-y-1.5">
                <div class="flex items-center justify-between font-semibold text-blue-700 dark:text-blue-400">
                    <span class="flex items-center gap-1.5">
                        <i class="pi pi-calculator text-xs"></i>
                        Kalkulasi Otomatis Prefix /{{ subnetPreview.cidr }}
                    </span>
                    <span class="font-mono text-[11px] bg-blue-100 dark:bg-blue-900/50 px-2 py-0.5 rounded text-blue-800 dark:text-blue-300">
                        {{ subnetPreview.mask }}
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-2 pt-1 border-t border-blue-200/60 dark:border-blue-800/40 text-[11px]">
                    <div>
                        <span class="text-muted-color">Total Alamat IP:</span>
                        <div class="font-bold text-surface-900 dark:text-surface-0 font-mono">{{ subnetPreview.total.toLocaleString() }} IP</div>
                    </div>
                    <div>
                        <span class="text-muted-color">Host Dapat Digunakan:</span>
                        <div class="font-bold text-emerald-600 dark:text-emerald-400 font-mono">{{ subnetPreview.usable.toLocaleString() }} Host</div>
                    </div>
                </div>
                <div class="text-[10px] text-muted-color leading-tight pt-1">
                    * Alamat pertama dialokasikan sebagai <b>Network Address</b> dan alamat terakhir sebagai <b>Broadcast Address</b>. Grid visualizer IPAM akan otomatis menampilkan sejumlah total IP di atas.
                </div>
            </div>

            <div>
                <label class="text-xs font-semibold text-muted-color">Keterangan / Lokasi Subnet</label>
                <InputText
                    v-model="formSubnet.keterangan"
                    placeholder="Contoh: VLAN 20 - Keuangan & Server"
                    class="w-full mt-1"
                />
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t border-surface-200 dark:border-surface-700">
                <Button label="Batal" severity="secondary" text @click="newSubnetDialog = false" />
                <Button label="Buat Subnet" icon="pi pi-check" severity="primary" :loading="savingSubnet" @click="saveNewSubnet" />
            </div>
        </div>
    </Dialog>

    <!-- ==================================================== -->
    <!-- DIALOG: DETAIL IP MIKROTIK ROUTER                    -->
    <!-- ==================================================== -->
    <Dialog
        v-model:visible="networkDlg"
        modal
        maximizable
        :header="'Detail ' + networkHeader"
        :style="{ width: '70vw' }"
        :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
    >
        <div class="flex w-full mb-4">
            <DataTable
                v-model:filters="filters"
                :value="tbList"
                paginator
                :rows="15"
                :rowsPerPageOptions="[5, 10, 15, 25, 50, 100]"
                dataKey=".id"
                filterDisplay="row"
                :loading="loading"
                :globalFilterFields="['address', 'interface', 'network']"
                class="w-full"
            >
                <template #header>
                    <div class="flex justify-end">
                        <IconField>
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="filters['global'].value" placeholder="Keyword Search" />
                        </IconField>
                    </div>
                </template>
                <template #empty> No network found. </template>
                <template #loading> Loading network data. Please wait. </template>

                <Column field="address" header="Address" style="width: 25%"></Column>
                <Column field="network" header="Network" style="width: 25%"></Column>
                <Column field="interface" header="Interface" style="width: 25%"></Column>
                <Column field="dynamic" header="Dynamic" style="width: 15%">
                    <template #body="slotProps">
                        <Badge :value="slotProps.data.dynamic" :severity="slotProps.data.dynamic === 'false' ? 'danger' : 'success'"></Badge>
                    </template>
                </Column>
                <Column field="" header="Aksi" style="width: 10%">
                    <template #body="slotProps">
                        <Button
                            icon="pi pi-arrow-right-arrow-left"
                            v-tooltip.bottom="'Ping ' + slotProps.data.address"
                            severity="secondary"
                            @click="ping(slotProps.data.address)"
                            rounded
                        />
                    </template>
                </Column>
            </DataTable>
        </div>
    </Dialog>

    <!-- ==================================================== -->
    <!-- DIALOG: SOCKET.IO PING REAL-TIME TERMINAL            -->
    <!-- ==================================================== -->
    <Dialog
        v-model:visible="terminalDlg"
        modal
        :header="terminalHeader"
        :style="{ width: '50vw' }"
        :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
        @hide="stop(pingAddress)"
    >
        <div class="grid-col w-full mb-4">
            <div>
                <Terminal
                    :welcomeMessage="statusMessage"
                    aria-label="Ping Terminal Service"
                    class="w-full mb-5 font-mono"
                    style="white-space: pre-line; min-height: 250px;"
                />
            </div>

            <div class="flex justify-end">
                <Button
                    icon="pi pi-stop-circle"
                    severity="danger"
                    label="Hentikan Ping"
                    @click="stop(pingAddress)"
                    rounded
                />
            </div>
        </div>
    </Dialog>
</template>

<style scoped>
.echart-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 16px;
    background-color: var(--surface-card, #ffffff);
    border-radius: 12px;
    border: 1px solid var(--surface-border, #e2e8f0);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    width: 100%;
    margin: 10px auto;
}

.chart {
    width: 100%;
    height: 420px;
    min-height: 320px;
}
</style>