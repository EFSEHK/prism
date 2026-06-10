import { useState } from 'react'
import { View, Text, Pressable, Modal, StyleSheet, ScrollView } from 'react-native'

export default function ViewAsPicker({ options, value, onChange }) {
  const [open, setOpen] = useState(false)
  const selectedLabel = options.find((r) => r.name === value)?.label ?? ''

  function select(name) {
    onChange(name)
    setOpen(false)
  }

  return (
    <>
      <View style={styles.control}>
        <Text style={styles.label}>{value ? 'Viewing as' : 'View as'}</Text>
        <Pressable onPress={() => setOpen(true)} style={styles.select} hitSlop={6}>
          <Text style={styles.selectText} numberOfLines={1}>
            {value ? selectedLabel : 'Select role…'}
          </Text>
        </Pressable>
      </View>
      <Modal visible={open} transparent animationType="fade" onRequestClose={() => setOpen(false)}>
        <Pressable style={styles.backdrop} onPress={() => setOpen(false)}>
          <View style={styles.sheet}>
            <Text style={styles.title}>{value ? 'Viewing as' : 'View as'}</Text>
            <ScrollView style={styles.list}>
              <Pressable style={styles.option} onPress={() => select('')}>
                <Text style={[styles.optionText, !value && styles.optionActive]}>Your account</Text>
              </Pressable>
              {options.map((role) => (
                <Pressable key={role.name} style={styles.option} onPress={() => select(role.name)}>
                  <Text style={[styles.optionText, value === role.name && styles.optionActive]}>
                    {role.label}
                  </Text>
                </Pressable>
              ))}
            </ScrollView>
          </View>
        </Pressable>
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
    maxHeight: '70%',
  },
  title: {
    fontSize: 16,
    fontWeight: '700',
    color: '#0f172a',
    marginBottom: 12,
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
  optionActive: {
    color: '#2563eb',
    fontWeight: '700',
  },
})
