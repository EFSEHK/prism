import { View, Text, Pressable, ScrollView, StyleSheet, Modal } from 'react-native'
import Svg, { Path } from 'react-native-svg'

export const NAV_ITEMS_STAFF = [
  { id: 'dashboard', label: 'Dashboard' },
  { id: 'attendance', label: 'Attendance' },
]

export const NAV_ITEMS_HOME = [{ id: 'home', label: 'Home' }]

export const NAV_ITEMS_CHILD = [
  { id: 'dashboard', label: 'Dashboard' },
  { id: 'homework', label: 'Homework' },
  { id: 'marks', label: 'Marks' },
  { id: 'attendance', label: 'Attendance' },
  { id: 'timetable', label: 'Timetable' },
  { id: 'notifications', label: 'Notifications' },
  { id: 'fees', label: 'Fees' },
  { id: 'online', label: 'Online Class' },
  { id: 'leave', label: 'Leave' },
  { id: 'home', label: 'Switch child' },
]

/** Student nav — same as child dashboard, without Switch child. */
export const NAV_ITEMS_STUDENT = NAV_ITEMS_CHILD.filter((item) => item.id !== 'home')

/** @deprecated Use NAV_ITEMS_HOME or NAV_ITEMS_CHILD */
export const NAV_ITEMS = NAV_ITEMS_CHILD

const ALWAYS_VISIBLE_NAV = new Set(['dashboard', 'home'])

/**
 * @param {Array<{ id: string, label: string }>} items
 * @param {Set<string>|string[]|null} enabledIds - module ids enabled by GET /efsc/modules
 */
function onlyEnabledNavItems(items, enabledIds) {
  if (!enabledIds) {
    return items.filter((item) => ALWAYS_VISIBLE_NAV.has(item.id))
  }
  const enabled = enabledIds instanceof Set ? enabledIds : new Set(enabledIds)
  return items.filter(
    (item) => ALWAYS_VISIBLE_NAV.has(item.id) || enabled.has(item.id),
  )
}

/**
 * Build staff side-nav from the module catalog (dashboard first, then enabled modules).
 * @param {Array<{ id: string, label?: string, enabled?: boolean, platforms?: string[] }>} modules
 */
export function staffNavItemsFromModules(modules = []) {
  const items = [{ id: 'dashboard', label: 'Dashboard' }]
  for (const mod of modules) {
    if (!mod || mod.id === 'dashboard') continue
    const status = mod.status
      || (mod.enabled === false ? 'disabled' : (mod.coming_soon ? 'coming_soon' : 'live'))
    if (status === 'disabled' || status === 'coming_soon') continue
    const platforms = mod.platforms || ['web', 'mobile']
    if (!platforms.includes('mobile')) continue
    items.push({ id: mod.id, label: mod.label || mod.id })
  }
  return items
}

/**
 * @param {boolean} hasSelectedChild
 * @param {boolean} isStaff
 * @param {boolean} isStudent
 * @param {Set<string>|string[]|null} enabledIds
 * @param {Set<string>|string[]|null} comingSoonIds - catalog coming_soon modules (excluded from nav)
 */
export function navItemsForContext(
  hasSelectedChild,
  isStaff = false,
  isStudent = false,
  enabledIds = null,
  comingSoonIds = null,
) {
  const comingSoon = comingSoonIds instanceof Set
    ? comingSoonIds
    : new Set(comingSoonIds || [])
  const filterComingSoon = (items) => items.filter((item) => !comingSoon.has(item.id))

  if (isStaff) return filterComingSoon(onlyEnabledNavItems(NAV_ITEMS_STAFF, enabledIds))
  if (isStudent) return filterComingSoon(onlyEnabledNavItems(NAV_ITEMS_STUDENT, enabledIds))
  if (!hasSelectedChild) return NAV_ITEMS_HOME
  return filterComingSoon(onlyEnabledNavItems(NAV_ITEMS_CHILD, enabledIds))
}

export function HamburgerIcon({ color = '#0f172a' }) {
  return (
    <View style={styles.hamburger}>
      <View style={[styles.bar, { backgroundColor: color }]} />
      <View style={[styles.bar, { backgroundColor: color }]} />
      <View style={[styles.bar, { backgroundColor: color }]} />
    </View>
  )
}

export function HomeIcon({ size = 22, color = '#0f172a' }) {
  return (
    <Svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
      accessibilityElementsHidden
      importantForAccessibility="no-hide-descendants"
    >
      <Path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1V9.5z" />
    </Svg>
  )
}

export default function SideMenu({ visible, active, items, onChange, onClose, onLogout }) {
  const navItems = items ?? NAV_ITEMS_CHILD
  const hasMultipleItems = navItems.length > 1

  function select(id) {
    onChange(id)
    onClose()
  }

  function handleLogout() {
    onClose()
    onLogout?.()
  }

  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onClose}>
      <View style={styles.overlay}>
        <Pressable style={styles.backdrop} onPress={onClose} accessibilityLabel="Close menu" />
        <View style={styles.drawer}>
          <Text style={styles.drawerTitle}>Menu</Text>
          <ScrollView showsVerticalScrollIndicator={false}>
            {navItems.map((item) => (
              <View key={`${item.id}-${item.label}`}>
                {hasMultipleItems && item.id === 'home' ? <View style={styles.menuSeparator} /> : null}
                <Pressable
                  onPress={() => select(item.id)}
                  style={[styles.item, active === item.id && styles.itemActive]}
                >
                  <Text style={[styles.label, active === item.id && styles.labelActive]}>
                    {item.label}
                  </Text>
                </Pressable>
              </View>
            ))}
            {onLogout ? (
              <>
                <View style={styles.menuSeparator} />
                <Pressable onPress={handleLogout} style={styles.item}>
                  <Text style={[styles.label, styles.logoutLabel]}>Logout</Text>
                </Pressable>
              </>
            ) : null}
          </ScrollView>
        </View>
      </View>
    </Modal>
  )
}

const styles = StyleSheet.create({
  overlay: {
    flex: 1,
    flexDirection: 'row',
  },
  backdrop: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.45)',
  },
  drawer: {
    width: 260,
    backgroundColor: '#fff',
    paddingTop: 48,
    paddingBottom: 16,
    borderLeftWidth: 1,
    borderLeftColor: '#e2e8f0',
  },
  drawerTitle: {
    fontSize: 13,
    fontWeight: '700',
    color: '#64748b',
    textTransform: 'uppercase',
    letterSpacing: 0.8,
    paddingHorizontal: 20,
    marginBottom: 12,
  },
  menuSeparator: {
    borderTopWidth: 1,
    borderTopColor: '#e2e8f0',
    marginTop: 8,
    marginBottom: 8,
    marginHorizontal: 20,
  },
  itemActive: {
    backgroundColor: '#eff6ff',
    borderRightColor: '#2563eb',
  },
  item: {
    paddingHorizontal: 20,
    paddingVertical: 14,
    borderRightWidth: 3,
    borderRightColor: 'transparent',
  },
  label: {
    fontSize: 16,
    fontWeight: '600',
    color: '#334155',
  },
  labelActive: {
    color: '#2563eb',
  },
  logoutLabel: {
    color: '#b91c1c',
  },
  hamburger: {
    width: 22,
    height: 16,
    justifyContent: 'space-between',
  },
  bar: {
    height: 2,
    borderRadius: 1,
    backgroundColor: '#0f172a',
  },
})
