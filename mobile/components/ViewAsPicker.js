import { useMemo, useState } from 'react'
import { useHardwareBack } from '../hooks/useHardwareBack'
import {
  View,
  Text,
  TextInput,
  Pressable,
  Modal,
  StyleSheet,
  ScrollView,
  ActivityIndicator,
} from 'react-native'

const BLOCKED_ROLES = ['superadmin', 'developer']

function userRoleNames(user) {
  return (user.roles || []).map((r) => r.name)
}

function formatRoles(user) {
  const names = userRoleNames(user)
  return names.length ? names.join(', ') : '—'
}

function canImpersonate(user) {
  return !userRoleNames(user).some((n) => BLOCKED_ROLES.includes(n))
}

export default function ViewAsPicker({
  options = [],
  users = [],
  value = '',
  usersLoading = false,
  onChangeRole,
  onChangeUser,
}) {
  const [open, setOpen] = useState(false)
  const [mode, setMode] = useState('role')
  const [search, setSearch] = useState('')

  useHardwareBack(() => {
    if (!open) return false
    setOpen(false)
    return true
  }, [open])

  const selectedLabel = options.find((r) => r.name === value)?.label ?? ''
  const impersonatableUsers = useMemo(
    () => users.filter(canImpersonate),
    [users],
  )
  const filteredUsers = useMemo(() => {
    const q = search.trim().toLowerCase()
    if (!q) return impersonatableUsers
    return impersonatableUsers.filter((u) => {
      const hay = `${u.name || ''} ${u.email || ''} ${formatRoles(u)}`.toLowerCase()
      return hay.includes(q)
    })
  }, [impersonatableUsers, search])

  function openPicker() {
    setMode('role')
    setSearch('')
    setOpen(true)
  }

  function selectRole(name) {
    onChangeRole?.(name)
    setOpen(false)
  }

  function selectUser(user) {
    onChangeUser?.(user)
    setOpen(false)
  }

  return (
    <>
      <View style={styles.control}>
        <Text style={styles.label}>{value ? 'Viewing as' : 'View as'}</Text>
        <Pressable onPress={openPicker} style={styles.select} hitSlop={6}>
          <Text style={styles.selectText} numberOfLines={1}>
            {value ? selectedLabel : 'Select…'}
          </Text>
        </Pressable>
      </View>
      <Modal visible={open} transparent animationType="fade" onRequestClose={() => setOpen(false)}>
        <View style={styles.backdrop}>
          <Pressable style={StyleSheet.absoluteFillObject} onPress={() => setOpen(false)} />
          <View style={styles.sheet}>
            <Text style={styles.title}>{value ? 'Viewing as' : 'View as'}</Text>
            <View style={styles.tabs}>
              <Pressable
                style={[styles.tab, mode === 'role' && styles.tabActive]}
                onPress={() => setMode('role')}
              >
                <Text style={[styles.tabText, mode === 'role' && styles.tabTextActive]}>Role</Text>
              </Pressable>
              <Pressable
                style={[styles.tab, mode === 'user' && styles.tabActive]}
                onPress={() => setMode('user')}
              >
                <Text style={[styles.tabText, mode === 'user' && styles.tabTextActive]}>User</Text>
              </Pressable>
            </View>

            {mode === 'role' ? (
              <ScrollView style={styles.list} keyboardShouldPersistTaps="handled">
                <Pressable style={styles.option} onPress={() => selectRole('')}>
                  <Text style={[styles.optionText, !value && styles.optionActive]}>Your account</Text>
                </Pressable>
                {options.map((role) => (
                  <Pressable key={role.name} style={styles.option} onPress={() => selectRole(role.name)}>
                    <Text style={[styles.optionText, value === role.name && styles.optionActive]}>
                      {role.label}
                    </Text>
                  </Pressable>
                ))}
              </ScrollView>
            ) : (
              <View style={styles.userPane}>
                <TextInput
                  style={styles.search}
                  value={search}
                  onChangeText={setSearch}
                  placeholder="Search users…"
                  placeholderTextColor="#94a3b8"
                  autoCapitalize="none"
                  autoCorrect={false}
                />
                {usersLoading ? (
                  <ActivityIndicator style={{ marginVertical: 24 }} color="#2563eb" />
                ) : (
                  <ScrollView style={styles.list} keyboardShouldPersistTaps="handled">
                    {filteredUsers.length === 0 ? (
                      <Text style={styles.empty}>No users found.</Text>
                    ) : (
                      filteredUsers.map((user) => (
                        <Pressable
                          key={user.id}
                          style={styles.option}
                          onPress={() => selectUser(user)}
                        >
                          <Text style={styles.optionText}>{user.name}</Text>
                          <Text style={styles.optionMeta} numberOfLines={1}>
                            {user.email} · {formatRoles(user)}
                          </Text>
                        </Pressable>
                      ))
                    )}
                  </ScrollView>
                )}
              </View>
            )}
          </View>
        </View>
      </Modal>
    </>
  )
}

const styles = StyleSheet.create({
  control: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    maxWidth: 160,
  },
  label: {
    fontSize: 11,
    color: '#64748b',
    fontWeight: '600',
  },
  select: {
    borderWidth: 1,
    borderColor: '#cbd5e1',
    borderRadius: 4,
    backgroundColor: '#f8fafc',
    paddingHorizontal: 8,
    paddingVertical: 4,
    maxWidth: 100,
  },
  selectText: {
    fontSize: 11,
    color: '#334155',
    fontWeight: '600',
  },
  backdrop: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.45)',
    justifyContent: 'center',
    padding: 24,
  },
  sheet: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    maxHeight: '75%',
    zIndex: 1,
  },
  title: {
    fontSize: 16,
    fontWeight: '700',
    color: '#0f172a',
    marginBottom: 12,
  },
  tabs: {
    flexDirection: 'row',
    backgroundColor: '#f1f5f9',
    borderRadius: 8,
    padding: 3,
    marginBottom: 12,
  },
  tab: {
    flex: 1,
    paddingVertical: 8,
    alignItems: 'center',
    borderRadius: 6,
  },
  tabActive: {
    backgroundColor: '#fff',
  },
  tabText: {
    fontSize: 13,
    fontWeight: '600',
    color: '#64748b',
  },
  tabTextActive: {
    color: '#2563eb',
  },
  userPane: {
    flexGrow: 1,
    minHeight: 200,
  },
  search: {
    borderWidth: 1,
    borderColor: '#cbd5e1',
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 10,
    marginBottom: 8,
    fontSize: 14,
    color: '#0f172a',
    backgroundColor: '#f8fafc',
  },
  list: {
    maxHeight: 360,
  },
  option: {
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#f1f5f9',
  },
  optionText: {
    fontSize: 15,
    color: '#334155',
  },
  optionMeta: {
    fontSize: 12,
    color: '#64748b',
    marginTop: 2,
  },
  optionActive: {
    color: '#2563eb',
    fontWeight: '700',
  },
  empty: {
    fontSize: 14,
    color: '#94a3b8',
    textAlign: 'center',
    paddingVertical: 24,
  },
})
