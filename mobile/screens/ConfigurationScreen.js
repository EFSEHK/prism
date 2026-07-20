import { useCallback, useEffect, useMemo, useState } from 'react'
import {
  View,
  Text,
  ScrollView,
  ActivityIndicator,
  TextInput,
  Pressable,
  StyleSheet,
  RefreshControl,
  Alert,
  Switch,
} from 'react-native'
import { apiClient } from '../apiClient'
import { formatError } from '../utils/format'
import { withTimeout } from '../utils/withTimeout'
import { Card, EmptyNote, Section, ui } from '../components/ui'

const ACADEMIC_ROLES = ['superadmin', 'admin', 'developer', 'computer_operator']
const ROSTER_ROLES = ['computer_operator', 'section_head', 'class_incharge']

function permissionNames(list) {
  return (list || []).map((p) => (typeof p === 'string' ? p : p?.name)).filter(Boolean)
}

function roleNameList(list) {
  return (list || []).map((r) => (typeof r === 'string' ? r : r?.name)).filter(Boolean)
}

function canManageAcademic(perms, roles) {
  return perms.includes('manage_academic_structure') || roles.some((r) => ACADEMIC_ROLES.includes(r))
}

function canManageRoster(perms, roles) {
  return perms.includes('manage_student_roster') || roles.some((r) => ROSTER_ROLES.includes(r))
}

function buildTabs(perms, roles) {
  const tabs = []
  if (canManageAcademic(perms, roles)) {
    tabs.push(
      { id: 'structure', label: 'Structure' },
      { id: 'subjects', label: 'Subjects' },
      { id: 'assign', label: 'Assign subjects' },
    )
  }
  if (canManageRoster(perms, roles)) {
    tabs.push({ id: 'enroll', label: 'Enroll students' })
  }
  return tabs
}

function classLabel(c) {
  const area = c?.area?.name
  return area ? `${c.name} (${area})` : c?.name || '—'
}

function ChipPicker({ label, value, options, onChange, disabled, emptyText }) {
  return (
    <View style={[styles.pickerWrap, disabled && styles.disabled]}>
      {label ? <Text style={styles.label}>{label}</Text> : null}
      {!options.length ? (
        <Text style={styles.muted}>{emptyText || 'None available'}</Text>
      ) : (
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chipRow}>
          {options.map((opt) => {
            const id = String(opt.id ?? opt.value)
            const active = value === id
            return (
              <Pressable
                key={id}
                onPress={() => !disabled && onChange(id)}
                style={[styles.chip, active && styles.chipActive]}
                disabled={disabled}
              >
                <Text style={[styles.chipText, active && styles.chipTextActive]}>
                  {opt.name ?? opt.label}
                </Text>
              </Pressable>
            )
          })}
        </ScrollView>
      )}
    </View>
  )
}

function PrimaryButton({ label, onPress, disabled }) {
  return (
    <Pressable
      onPress={onPress}
      disabled={disabled}
      style={[styles.btn, disabled && styles.btnDisabled]}
    >
      <Text style={styles.btnText}>{label}</Text>
    </Pressable>
  )
}

function SecondaryButton({ label, onPress, disabled }) {
  return (
    <Pressable
      onPress={onPress}
      disabled={disabled}
      style={[styles.btnSecondary, disabled && styles.btnDisabled]}
    >
      <Text style={styles.btnSecondaryText}>{label}</Text>
    </Pressable>
  )
}

function RowActions({ onEdit, onDelete }) {
  return (
    <View style={styles.rowActions}>
      {onEdit ? (
        <Pressable onPress={onEdit} style={styles.rowBtn}>
          <Text style={styles.rowBtnText}>Edit</Text>
        </Pressable>
      ) : null}
      {onDelete ? (
        <Pressable onPress={onDelete} style={[styles.rowBtn, styles.rowBtnDanger]}>
          <Text style={[styles.rowBtnText, styles.rowBtnDangerText]}>Delete</Text>
        </Pressable>
      ) : null}
    </View>
  )
}

function confirmDelete(title, message, onConfirm) {
  Alert.alert(title, message, [
    { text: 'Cancel', style: 'cancel' },
    { text: 'Delete', style: 'destructive', onPress: onConfirm },
  ])
}

function StructureTab({ flashOk, flashErr }) {
  const [years, setYears] = useState([])
  const [areas, setAreas] = useState([])
  const [classes, setClasses] = useState([])
  const [sections, setSections] = useState([])
  const [sectionHeads, setSectionHeads] = useState([])
  const [yearId, setYearId] = useState('')
  const [areaId, setAreaId] = useState('')
  const [classId, setClassId] = useState('')
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [formOpen, setFormOpen] = useState(null)
  const [editingId, setEditingId] = useState(null)
  const [yearForm, setYearForm] = useState({ name: '', starts_on: '', ends_on: '', is_current: false })
  const [areaForm, setAreaForm] = useState({ name: '', sectionHeadUserId: '' })
  const [nameForm, setNameForm] = useState('')

  const level = !yearId ? 'year' : !areaId ? 'area' : !classId ? 'class' : 'section'
  const levelSingular = { year: 'session year', area: 'area', class: 'class', section: 'section' }[level]
  const levelTitle = { year: 'Session years', area: 'Areas', class: 'Classes', section: 'Sections' }[level]

  const breadcrumb = useMemo(() => {
    const crumbs = []
    const year = years.find((y) => String(y.id) === String(yearId))
    if (year) crumbs.push({ level: 'year', id: year.id, label: year.name })
    const area = areas.find((a) => String(a.id) === String(areaId))
    if (area) crumbs.push({ level: 'area', id: area.id, label: area.name })
    const cls = classes.find((c) => String(c.id) === String(classId))
    if (cls) crumbs.push({ level: 'class', id: cls.id, label: cls.name })
    return crumbs
  }, [years, areas, classes, yearId, areaId, classId])

  const loadYears = useCallback(async () => {
    const { data } = await apiClient.get('/efsc/academic/years')
    setYears(data?.data ?? data ?? [])
  }, [])

  const loadAreas = useCallback(async (yId) => {
    if (!yId) {
      setAreas([])
      return
    }
    const { data } = await apiClient.get('/efsc/academic/areas', {
      params: { academic_year_id: yId },
    })
    setAreas(data?.data ?? data ?? [])
  }, [])

  const loadClasses = useCallback(async (aId) => {
    if (!aId) {
      setClasses([])
      return
    }
    const { data } = await apiClient.get('/efsc/academic/classes', {
      params: { area_id: aId },
    })
    setClasses(data?.data ?? data ?? [])
  }, [])

  const loadSections = useCallback(async (cId) => {
    if (!cId) {
      setSections([])
      return
    }
    const { data } = await apiClient.get('/efsc/academic/sections', {
      params: { school_class_id: cId },
    })
    setSections(data?.data ?? data ?? [])
  }, [])

  async function loadSectionHeads() {
    const { data } = await apiClient.get('/efsc/academic/section-heads')
    setSectionHeads(data?.data ?? data ?? [])
  }

  useEffect(() => {
    let cancelled = false
    setLoading(true)
    withTimeout(loadYears(), 20000, 'Session years')
      .catch((e) => {
        if (!cancelled) flashErr(e, 'Failed to load session years')
      })
      .finally(() => {
        if (!cancelled) setLoading(false)
      })
    return () => {
      cancelled = true
    }
  }, [loadYears, flashErr])

  function closeForm() {
    setFormOpen(null)
    setEditingId(null)
    setYearForm({ name: '', starts_on: '', ends_on: '', is_current: false })
    setAreaForm({ name: '', sectionHeadUserId: '' })
    setNameForm('')
  }

  function openAdd() {
    closeForm()
    setFormOpen(level)
    if (level === 'area') loadSectionHeads().catch(() => {})
  }

  function openEditYear(y) {
    closeForm()
    setEditingId(y.id)
    setYearForm({
      name: y.name || '',
      starts_on: y.starts_on || '',
      ends_on: y.ends_on || '',
      is_current: !!y.is_current,
    })
    setFormOpen('year')
  }

  function openEditArea(a) {
    closeForm()
    setEditingId(a.id)
    setAreaForm({
      name: a.name || '',
      sectionHeadUserId: a.section_head_user_id ? String(a.section_head_user_id) : '',
    })
    setFormOpen('area')
    loadSectionHeads().catch(() => {})
  }

  function openEditClass(c) {
    closeForm()
    setEditingId(c.id)
    setNameForm(c.name || '')
    setFormOpen('class')
  }

  function openEditSection(s) {
    closeForm()
    setEditingId(s.id)
    setNameForm(s.name || '')
    setFormOpen('section')
  }

  function selectYear(y) {
    setYearId(String(y.id))
    setAreaId('')
    setClassId('')
    setClasses([])
    setSections([])
    closeForm()
    loadAreas(y.id).catch((e) => flashErr(e, 'Failed to load areas'))
  }

  function selectArea(a) {
    setAreaId(String(a.id))
    setClassId('')
    setSections([])
    closeForm()
    loadClasses(a.id).catch((e) => flashErr(e, 'Failed to load classes'))
  }

  function selectClass(c) {
    setClassId(String(c.id))
    closeForm()
    loadSections(c.id).catch((e) => flashErr(e, 'Failed to load sections'))
  }

  function navigateStructure(index) {
    closeForm()
    if (index < 0) {
      setYearId('')
      setAreaId('')
      setClassId('')
      setAreas([])
      setClasses([])
      setSections([])
      return
    }
    const crumb = breadcrumb[index]
    if (crumb.level === 'year') {
      setAreaId('')
      setClassId('')
      setClasses([])
      setSections([])
      loadAreas(crumb.id).catch((e) => flashErr(e, 'Failed to load areas'))
    } else if (crumb.level === 'area') {
      setClassId('')
      setSections([])
      loadClasses(crumb.id).catch((e) => flashErr(e, 'Failed to load classes'))
    } else if (crumb.level === 'class') {
      loadSections(crumb.id).catch((e) => flashErr(e, 'Failed to load sections'))
    }
  }

  async function submitForm() {
    setSaving(true)
    try {
      if (formOpen === 'year') {
        if (editingId) {
          await apiClient.put(`/efsc/academic/years/${editingId}`, yearForm)
          flashOk('Session year updated.')
        } else {
          await apiClient.post('/efsc/academic/years', yearForm)
          flashOk('Session year created.')
        }
        await loadYears()
      } else if (formOpen === 'area') {
        const payload = {
          name: areaForm.name,
          section_head_user_id: areaForm.sectionHeadUserId
            ? Number(areaForm.sectionHeadUserId)
            : null,
        }
        if (editingId) {
          await apiClient.put(`/efsc/academic/areas/${editingId}`, payload)
          flashOk('Area updated.')
        } else {
          await apiClient.post('/efsc/academic/areas', {
            academic_year_id: Number(yearId),
            ...payload,
          })
          flashOk('Area created.')
        }
        await loadAreas(yearId)
      } else if (formOpen === 'class') {
        if (editingId) {
          await apiClient.put(`/efsc/academic/classes/${editingId}`, { name: nameForm })
          flashOk('Class updated.')
        } else {
          await apiClient.post('/efsc/academic/classes', {
            area_id: Number(areaId),
            name: nameForm,
          })
          flashOk('Class created.')
        }
        await loadClasses(areaId)
      } else if (formOpen === 'section') {
        if (editingId) {
          await apiClient.put(`/efsc/academic/sections/${editingId}`, { name: nameForm })
          flashOk('Section updated.')
        } else {
          await apiClient.post('/efsc/academic/sections', {
            school_class_id: Number(classId),
            name: nameForm,
          })
          flashOk('Section created.')
        }
        await loadSections(classId)
      }
      closeForm()
    } catch (e) {
      flashErr(e, `Failed to ${editingId ? 'update' : 'create'} ${levelSingular}`)
    } finally {
      setSaving(false)
    }
  }

  function deleteYear(y) {
    confirmDelete(
      'Delete session year',
      `Delete "${y.name}"? All areas, classes, and sections under it will be removed.`,
      async () => {
        try {
          await apiClient.delete(`/efsc/academic/years/${y.id}`)
          if (String(yearId) === String(y.id)) navigateStructure(-1)
          await loadYears()
          flashOk('Session year deleted.')
        } catch (e) {
          flashErr(e, 'Failed to delete session year')
        }
      },
    )
  }

  function deleteArea(a) {
    confirmDelete(
      'Delete area',
      `Delete "${a.name}"? All classes and sections under it will be removed.`,
      async () => {
        try {
          await apiClient.delete(`/efsc/academic/areas/${a.id}`)
          if (String(areaId) === String(a.id)) {
            setAreaId('')
            setClassId('')
            setClasses([])
            setSections([])
          }
          await loadAreas(yearId)
          flashOk('Area deleted.')
        } catch (e) {
          flashErr(e, 'Failed to delete area')
        }
      },
    )
  }

  function deleteClass(c) {
    confirmDelete(
      'Delete class',
      `Delete "${c.name}"? All sections under it will be removed.`,
      async () => {
        try {
          await apiClient.delete(`/efsc/academic/classes/${c.id}`)
          if (String(classId) === String(c.id)) {
            setClassId('')
            setSections([])
          }
          await loadClasses(areaId)
          flashOk('Class deleted.')
        } catch (e) {
          flashErr(e, 'Failed to delete class')
        }
      },
    )
  }

  function deleteSection(s) {
    confirmDelete('Delete section', `Delete "${s.name}"?`, async () => {
      try {
        await apiClient.delete(`/efsc/academic/sections/${s.id}`)
        await loadSections(classId)
        flashOk('Section deleted.')
      } catch (e) {
        flashErr(e, 'Failed to delete section')
      }
    })
  }

  if (loading) return <ActivityIndicator style={styles.center} />

  const items =
    level === 'year' ? years : level === 'area' ? areas : level === 'class' ? classes : sections

  return (
    <View>
      <Text style={styles.h2}>School structure</Text>
      <Text style={styles.hint}>Tap a row to drill down. Year → Area → Class → Section.</Text>

      {breadcrumb.length ? (
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.crumbRow}>
          <Pressable onPress={() => navigateStructure(-1)}>
            <Text style={styles.crumb}>Session years</Text>
          </Pressable>
          {breadcrumb.map((crumb, i) => (
            <View key={`${crumb.level}-${crumb.id}`} style={styles.crumbItem}>
              <Text style={styles.crumbSep}>›</Text>
              <Pressable onPress={() => navigateStructure(i)}>
                <Text style={[styles.crumb, i === breadcrumb.length - 1 && styles.crumbCurrent]}>
                  {crumb.label}
                </Text>
              </Pressable>
            </View>
          ))}
        </ScrollView>
      ) : null}

      <View style={styles.toolbar}>
        <Text style={styles.h3}>{levelTitle}</Text>
        <Pressable onPress={openAdd} style={styles.addBtn}>
          <Text style={styles.addBtnText}>Add {levelSingular}</Text>
        </Pressable>
      </View>

      {formOpen ? (
        <View style={styles.formCard}>
          <Text style={styles.h3}>
            {editingId ? 'Edit' : 'Add'} {levelSingular}
          </Text>
          {formOpen === 'year' ? (
            <>
              <Text style={styles.label}>Name</Text>
              <TextInput
                style={styles.input}
                value={yearForm.name}
                onChangeText={(v) => setYearForm((f) => ({ ...f, name: v }))}
                placeholder="2025–2026"
              />
              <Text style={styles.label}>Starts (YYYY-MM-DD)</Text>
              <TextInput
                style={styles.input}
                value={yearForm.starts_on}
                onChangeText={(v) => setYearForm((f) => ({ ...f, starts_on: v }))}
                placeholder="2025-08-01"
              />
              <Text style={styles.label}>Ends (YYYY-MM-DD)</Text>
              <TextInput
                style={styles.input}
                value={yearForm.ends_on}
                onChangeText={(v) => setYearForm((f) => ({ ...f, ends_on: v }))}
                placeholder="2026-05-31"
              />
              <View style={styles.switchRow}>
                <Text style={styles.labelInline}>Current session</Text>
                <Switch
                  value={yearForm.is_current}
                  onValueChange={(v) => setYearForm((f) => ({ ...f, is_current: v }))}
                />
              </View>
            </>
          ) : null}
          {formOpen === 'area' ? (
            <>
              <Text style={styles.label}>Area name</Text>
              <TextInput
                style={styles.input}
                value={areaForm.name}
                onChangeText={(v) => setAreaForm((f) => ({ ...f, name: v }))}
                placeholder="Primary"
              />
              <ChipPicker
                label="Section head"
                value={areaForm.sectionHeadUserId}
                options={sectionHeads.map((u) => ({
                  id: String(u.id),
                  name: u.email ? `${u.name} (${u.email})` : u.name,
                }))}
                onChange={(id) =>
                  setAreaForm((f) => ({
                    ...f,
                    sectionHeadUserId: f.sectionHeadUserId === id ? '' : id,
                  }))
                }
                emptyText="No section heads found"
              />
            </>
          ) : null}
          {formOpen === 'class' || formOpen === 'section' ? (
            <>
              <Text style={styles.label}>{formOpen === 'class' ? 'Class name' : 'Section name'}</Text>
              <TextInput
                style={styles.input}
                value={nameForm}
                onChangeText={setNameForm}
                placeholder={formOpen === 'class' ? '10th' : 'White'}
              />
            </>
          ) : null}
          <View style={styles.formActions}>
            <SecondaryButton label="Cancel" onPress={closeForm} />
            <PrimaryButton
              label={saving ? (editingId ? 'Saving…' : 'Adding…') : editingId ? 'Save' : 'Add'}
              onPress={submitForm}
              disabled={saving}
            />
          </View>
        </View>
      ) : null}

      {!items.length ? (
        <EmptyNote
          text={
            level === 'year'
              ? 'No session years yet. Tap Add session year to create one.'
              : `No ${levelTitle.toLowerCase()} yet.`
          }
        />
      ) : null}

      {level === 'year'
        ? years.map((y) => (
            <View key={y.id}>
              <Card
                title={y.name}
                meta={`${y.starts_on || '—'} → ${y.ends_on || '—'}${y.is_current ? ' · Current' : ''}`}
                onPress={() => selectYear(y)}
              />
              <RowActions onEdit={() => openEditYear(y)} onDelete={() => deleteYear(y)} />
            </View>
          ))
        : null}

      {level === 'area'
        ? areas.map((a) => (
            <View key={a.id}>
              <Card
                title={a.name}
                meta={a.section_head?.name || a.sectionHead?.name || 'No section head'}
                onPress={() => selectArea(a)}
              />
              <RowActions onEdit={() => openEditArea(a)} onDelete={() => deleteArea(a)} />
            </View>
          ))
        : null}

      {level === 'class'
        ? classes.map((c) => (
            <View key={c.id}>
              <Card title={c.name} onPress={() => selectClass(c)} />
              <RowActions onEdit={() => openEditClass(c)} onDelete={() => deleteClass(c)} />
            </View>
          ))
        : null}

      {level === 'section'
        ? sections.map((s) => (
            <View key={s.id}>
              <Card title={s.name} />
              <RowActions onEdit={() => openEditSection(s)} onDelete={() => deleteSection(s)} />
            </View>
          ))
        : null}
    </View>
  )
}

function SubjectsTab({ flashOk, flashErr }) {
  const [subjects, setSubjects] = useState([])
  const [name, setName] = useState('')
  const [code, setCode] = useState('')
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)

  async function load() {
    const { data } = await apiClient.get('/efsc/academic/subjects')
    setSubjects(data?.data ?? data ?? [])
  }

  useEffect(() => {
    let cancelled = false
    setLoading(true)
    withTimeout(load(), 20000, 'Subjects')
      .catch((e) => {
        if (!cancelled) flashErr(e, 'Failed to load subjects')
      })
      .finally(() => {
        if (!cancelled) setLoading(false)
      })
    return () => {
      cancelled = true
    }
  }, [flashErr])

  async function createSubject() {
    if (!name.trim()) return
    setSaving(true)
    try {
      await apiClient.post('/efsc/academic/subjects', {
        name: name.trim(),
        code: code.trim() || null,
      })
      setName('')
      setCode('')
      await load()
      flashOk('Subject created.')
    } catch (e) {
      flashErr(e, 'Failed to create subject')
    } finally {
      setSaving(false)
    }
  }

  if (loading) return <ActivityIndicator style={styles.center} />

  return (
    <View>
      <Text style={styles.h2}>Subject catalog</Text>
      <View style={styles.formCard}>
        <Text style={styles.label}>Name</Text>
        <TextInput style={styles.input} value={name} onChangeText={setName} placeholder="Mathematics" />
        <Text style={styles.label}>Code</Text>
        <TextInput style={styles.input} value={code} onChangeText={setCode} placeholder="MATH" />
        <PrimaryButton
          label={saving ? 'Adding…' : 'Add subject'}
          onPress={createSubject}
          disabled={saving || !name.trim()}
        />
      </View>
      <Section title={`Subjects (${subjects.length})`}>
        {!subjects.length ? <EmptyNote text="No subjects yet." /> : null}
        {subjects.map((s) => (
          <Card key={s.id} title={s.name} meta={s.code || undefined} />
        ))}
      </Section>
    </View>
  )
}

function AssignTab({ flashOk, flashErr }) {
  const [groups, setGroups] = useState([])
  const [subjects, setSubjects] = useState([])
  const [groupId, setGroupId] = useState('')
  const [assignedIds, setAssignedIds] = useState([])
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [addingGroup, setAddingGroup] = useState(false)
  const [groupName, setGroupName] = useState('')

  async function loadAll() {
    const [g, s] = await Promise.all([
      apiClient.get('/efsc/academic/study-groups'),
      apiClient.get('/efsc/academic/subjects'),
    ])
    const nextGroups = g.data?.data ?? g.data ?? []
    setGroups(nextGroups)
    setSubjects(s.data?.data ?? s.data ?? [])
    return nextGroups
  }

  useEffect(() => {
    let cancelled = false
    setLoading(true)
    withTimeout(loadAll(), 20000, 'Assign subjects')
      .catch((e) => {
        if (!cancelled) flashErr(e, 'Failed to load study groups')
      })
      .finally(() => {
        if (!cancelled) setLoading(false)
      })
    return () => {
      cancelled = true
    }
  }, [flashErr])

  function selectGroup(id) {
    setGroupId(id)
    const group = groups.find((g) => String(g.id) === String(id))
    setAssignedIds((group?.subjects || []).map((s) => s.id))
  }

  function toggleSubject(id) {
    setAssignedIds((prev) =>
      prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id],
    )
  }

  async function saveAssignment() {
    if (!groupId) return
    setSaving(true)
    try {
      await apiClient.put(`/efsc/academic/study-groups/${groupId}/subjects`, {
        subject_ids: assignedIds,
      })
      const next = await loadAll()
      const fresh = next.find((g) => String(g.id) === String(groupId))
      setAssignedIds((fresh?.subjects || []).map((s) => s.id))
      flashOk('Subjects assigned.')
    } catch (e) {
      flashErr(e, 'Failed to assign subjects')
    } finally {
      setSaving(false)
    }
  }

  async function createGroup() {
    if (!groupName.trim()) return
    setSaving(true)
    try {
      const { data } = await apiClient.post('/efsc/academic/study-groups', {
        name: groupName.trim(),
      })
      setGroupName('')
      setAddingGroup(false)
      const next = await loadAll()
      const createdId = data?.id ?? data?.data?.id
      if (createdId) {
        setGroupId(String(createdId))
        setAssignedIds([])
      } else if (next.length) {
        const last = next[next.length - 1]
        setGroupId(String(last.id))
        setAssignedIds((last.subjects || []).map((s) => s.id))
      }
      flashOk('Study group created.')
    } catch (e) {
      flashErr(e, 'Failed to create study group')
    } finally {
      setSaving(false)
    }
  }

  if (loading) return <ActivityIndicator style={styles.center} />

  return (
    <View>
      <Text style={styles.h2}>Study groups & subject assignment</Text>
      <Text style={styles.hint}>Create study groups and assign subjects to each group.</Text>

      <View style={styles.toolbar}>
        <Text style={styles.h3}>Study group</Text>
        <Pressable onPress={() => setAddingGroup((v) => !v)} style={styles.addBtn}>
          <Text style={styles.addBtnText}>{addingGroup ? 'Cancel' : 'Add group'}</Text>
        </Pressable>
      </View>

      {addingGroup ? (
        <View style={styles.formCard}>
          <Text style={styles.label}>Study group name</Text>
          <TextInput
            style={styles.input}
            value={groupName}
            onChangeText={setGroupName}
            placeholder="Pre-Engineering"
          />
          <PrimaryButton
            label={saving ? 'Adding…' : 'Add'}
            onPress={createGroup}
            disabled={saving || !groupName.trim()}
          />
        </View>
      ) : null}

      <ChipPicker
        value={groupId}
        options={groups}
        onChange={selectGroup}
        emptyText="No study groups yet."
      />

      {groupId ? (
        <>
          <Text style={styles.label}>Subjects</Text>
          {!subjects.length ? <EmptyNote text="No subjects in catalog yet." /> : null}
          {subjects.map((s) => {
            const checked = assignedIds.includes(s.id)
            return (
              <Pressable
                key={s.id}
                onPress={() => toggleSubject(s.id)}
                style={[styles.checkRow, checked && styles.checkRowActive]}
              >
                <Text style={styles.checkMark}>{checked ? '✓' : ''}</Text>
                <Text style={styles.checkLabel}>
                  {s.name}
                  {s.code ? ` (${s.code})` : ''}
                </Text>
              </Pressable>
            )
          })}
          <PrimaryButton
            label={saving ? 'Saving…' : 'Save subject assignment'}
            onPress={saveAssignment}
            disabled={saving}
          />
        </>
      ) : null}
    </View>
  )
}

function EnrollTab({ flashOk, flashErr }) {
  const [classes, setClasses] = useState([])
  const [groups, setGroups] = useState([])
  const [filterSections, setFilterSections] = useState([])
  const [formSections, setFormSections] = useState([])
  const [students, setStudents] = useState([])
  const [filterClassId, setFilterClassId] = useState('')
  const [filterSectionId, setFilterSectionId] = useState('')
  const [filterGroupId, setFilterGroupId] = useState('')
  const [search, setSearch] = useState('')
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [formOpen, setFormOpen] = useState(false)
  const [form, setForm] = useState({
    name: '',
    admission_no: '',
    classId: '',
    sectionId: '',
    studyGroupId: '',
    roll_no: '',
    cnic: '',
    father_name: '',
    father_cnic: '',
    guardian_name: '',
    guardian_cnic: '',
    father_is_guardian: false,
  })

  async function loadBootstrap() {
    const [c, g] = await Promise.all([
      apiClient.get('/efsc/academic/classes'),
      apiClient.get('/efsc/academic/study-groups'),
    ])
    setClasses(c.data?.data ?? c.data ?? [])
    setGroups(g.data?.data ?? g.data ?? [])
  }

  useEffect(() => {
    let cancelled = false
    setLoading(true)
    withTimeout(loadBootstrap(), 20000, 'Enrollment')
      .catch((e) => {
        if (!cancelled) flashErr(e, 'Failed to load enrollment data')
      })
      .finally(() => {
        if (!cancelled) setLoading(false)
      })
    return () => {
      cancelled = true
    }
  }, [flashErr])

  async function loadFilterSections(classId) {
    if (!classId) {
      setFilterSections([])
      return
    }
    const { data } = await apiClient.get('/efsc/academic/sections', {
      params: { school_class_id: classId },
    })
    setFilterSections(data?.data ?? data ?? [])
  }

  async function loadFormSections(classId) {
    if (!classId) {
      setFormSections([])
      return
    }
    const { data } = await apiClient.get('/efsc/academic/sections', {
      params: { school_class_id: classId },
    })
    setFormSections(data?.data ?? data ?? [])
  }

  async function loadStudents(groupId, sectionId, classId) {
    if (!classId || !sectionId) {
      setStudents([])
      return
    }
    const params = { section_id: sectionId }
    if (groupId) params.study_group_id = groupId
    const { data } = await apiClient.get('/efsc/students', { params })
    setStudents(data?.data ?? data ?? [])
  }

  async function onFilterClass(id) {
    setFilterClassId(id)
    setFilterSectionId('')
    setFilterGroupId('')
    setSearch('')
    setStudents([])
    await loadFilterSections(id)
  }

  async function onFilterSection(id) {
    setFilterSectionId(id)
    setSearch('')
    await loadStudents(filterGroupId, id, filterClassId)
  }

  async function onFilterGroup(id) {
    setFilterGroupId(id)
    setSearch('')
    await loadStudents(id, filterSectionId, filterClassId)
  }

  function openForm() {
    setForm({
      name: '',
      admission_no: '',
      classId: filterClassId || '',
      sectionId: filterSectionId || '',
      studyGroupId: filterGroupId || '',
      roll_no: '',
      cnic: '',
      father_name: '',
      father_cnic: '',
      guardian_name: '',
      guardian_cnic: '',
      father_is_guardian: false,
    })
    if (filterClassId) loadFormSections(filterClassId).catch(() => {})
    else setFormSections([])
    setFormOpen(true)
  }

  function updateForm(patch) {
    setForm((prev) => {
      const next = { ...prev, ...patch }
      if (next.father_is_guardian) {
        next.guardian_name = next.father_name
        next.guardian_cnic = next.father_cnic
      }
      return next
    })
  }

  async function enrollStudent() {
    if (!form.studyGroupId || !form.name.trim() || !form.admission_no.trim()) return
    setSaving(true)
    try {
      const { data } = await apiClient.post('/efsc/students', {
        study_group_id: Number(form.studyGroupId),
        name: form.name.trim(),
        admission_no: form.admission_no.trim(),
        section_id: form.sectionId ? Number(form.sectionId) : null,
        roll_no: form.roll_no || null,
        cnic: form.cnic || null,
        father_name: form.father_name || null,
        father_cnic: form.father_cnic || null,
        guardian_name: form.guardian_name || null,
        guardian_cnic: form.guardian_cnic || null,
        father_is_guardian: form.father_is_guardian,
      })
      setFormOpen(false)
      if (form.classId) {
        setFilterClassId(form.classId)
        await loadFilterSections(form.classId)
      }
      setFilterSectionId(form.sectionId || '')
      setFilterGroupId(String(form.studyGroupId))
      await loadStudents(form.studyGroupId, form.sectionId, form.classId)
      const accountLines = []
      const accounts = data?.accounts
      if (accounts?.student?.email) {
        accountLines.push(`Student: ${accounts.student.email.split('@')[0]}`)
      }
      for (const p of accounts?.parents ?? []) {
        if (p.email) accountLines.push(`${p.name}: ${p.email.split('@')[0]}`)
      }
      const suffix = accountLines.length ? ` Accounts — ${accountLines.join('; ')}.` : ''
      flashOk(`Student enrolled.${suffix}`)
    } catch (e) {
      flashErr(e, 'Failed to enroll student')
    } finally {
      setSaving(false)
    }
  }

  const filtered = useMemo(() => {
    const q = search.trim().toLowerCase()
    if (!q) return students
    return students.filter((st) => {
      const haystack = [
        st.first_name,
        st.last_name,
        st.admission_no,
        st.roll_no,
        st.cnic,
        st.section?.name,
        st.study_group?.name || st.studyGroup?.name,
      ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase()
      return haystack.includes(q)
    })
  }, [students, search])

  if (loading) return <ActivityIndicator style={styles.center} />

  return (
    <View>
      <View style={styles.toolbar}>
        <Text style={styles.h2}>Student enrollment</Text>
        <Pressable onPress={openForm} style={styles.addBtn}>
          <Text style={styles.addBtnText}>Add new</Text>
        </Pressable>
      </View>

      <ChipPicker
        label="Class"
        value={filterClassId}
        options={classes.map((c) => ({ id: c.id, name: classLabel(c) }))}
        onChange={onFilterClass}
        emptyText="No classes yet."
      />
      <ChipPicker
        label="Section"
        value={filterSectionId}
        options={filterSections}
        onChange={onFilterSection}
        disabled={!filterClassId}
        emptyText={filterClassId ? 'No sections.' : 'Select a class first.'}
      />
      <ChipPicker
        label="Study group"
        value={filterGroupId}
        options={groups}
        onChange={onFilterGroup}
        emptyText="No study groups yet."
      />
      <TextInput
        style={styles.input}
        value={search}
        onChangeText={setSearch}
        placeholder="Search students…"
      />

      {formOpen ? (
        <View style={styles.formCard}>
          <Text style={styles.h3}>Enroll student</Text>
          <Text style={styles.label}>Student name</Text>
          <TextInput
            style={styles.input}
            value={form.name}
            onChangeText={(v) => updateForm({ name: v })}
            placeholder="Full name"
          />
          <Text style={styles.label}>CNIC</Text>
          <TextInput
            style={styles.input}
            value={form.cnic}
            onChangeText={(v) => updateForm({ cnic: v })}
            placeholder="xxxxx-xxxxxxx-x"
          />
          <Text style={styles.label}>Admission no.</Text>
          <TextInput
            style={styles.input}
            value={form.admission_no}
            onChangeText={(v) => updateForm({ admission_no: v })}
            placeholder="A-25-0016"
          />
          <ChipPicker
            label="Class"
            value={form.classId}
            options={classes.map((c) => ({ id: c.id, name: classLabel(c) }))}
            onChange={(id) => {
              updateForm({ classId: id, sectionId: '' })
              loadFormSections(id).catch(() => {})
            }}
          />
          <ChipPicker
            label="Section"
            value={form.sectionId}
            options={formSections}
            onChange={(id) => updateForm({ sectionId: id })}
            disabled={!form.classId}
          />
          <Text style={styles.label}>Roll no.</Text>
          <TextInput
            style={styles.input}
            value={form.roll_no}
            onChangeText={(v) => updateForm({ roll_no: v })}
          />
          <ChipPicker
            label="Study group"
            value={form.studyGroupId}
            options={groups}
            onChange={(id) => updateForm({ studyGroupId: id })}
          />
          <Text style={styles.label}>Father name</Text>
          <TextInput
            style={styles.input}
            value={form.father_name}
            onChangeText={(v) => updateForm({ father_name: v })}
          />
          <Text style={styles.label}>Father CNIC</Text>
          <TextInput
            style={styles.input}
            value={form.father_cnic}
            onChangeText={(v) => updateForm({ father_cnic: v })}
          />
          <View style={styles.switchRow}>
            <Text style={styles.labelInline}>Father is guardian</Text>
            <Switch
              value={form.father_is_guardian}
              onValueChange={(v) => updateForm({ father_is_guardian: v })}
            />
          </View>
          <Text style={styles.label}>Guardian name</Text>
          <TextInput
            style={[styles.input, form.father_is_guardian && styles.inputDisabled]}
            value={form.guardian_name}
            onChangeText={(v) => updateForm({ guardian_name: v })}
            editable={!form.father_is_guardian}
          />
          <Text style={styles.label}>Guardian CNIC</Text>
          <TextInput
            style={[styles.input, form.father_is_guardian && styles.inputDisabled]}
            value={form.guardian_cnic}
            onChangeText={(v) => updateForm({ guardian_cnic: v })}
            editable={!form.father_is_guardian}
          />
          <View style={styles.formActions}>
            <SecondaryButton label="Cancel" onPress={() => setFormOpen(false)} />
            <PrimaryButton
              label={saving ? 'Enrolling…' : 'Enroll student'}
              onPress={enrollStudent}
              disabled={saving || !form.studyGroupId || !form.name.trim() || !form.admission_no.trim()}
            />
          </View>
        </View>
      ) : null}

      {!filterClassId || !filterSectionId ? (
        <EmptyNote text="Select a class and section to view enrolled students, or tap Add new to enroll." />
      ) : filtered.length ? (
        filtered.map((st) => (
          <Card
            key={st.id}
            title={`${st.first_name || ''} ${st.last_name || ''}`.trim() || 'Student'}
            meta={[
              st.section?.school_class?.name || st.section?.schoolClass?.name,
              st.section?.name,
              st.study_group?.name || st.studyGroup?.name,
            ]
              .filter(Boolean)
              .join(' · ')}
            sub={[st.admission_no, st.roll_no].filter(Boolean).join(' · ') || undefined}
          />
        ))
      ) : (
        <EmptyNote
          text={
            students.length
              ? 'No students match your search or filters.'
              : 'No students enrolled in this class and section yet.'
          }
        />
      )}
    </View>
  )
}

export default function ConfigurationScreen({ permissions = [], roles = [] }) {
  const perms = useMemo(() => permissionNames(permissions), [permissions])
  const roleList = useMemo(() => roleNameList(roles), [roles])
  const tabs = useMemo(() => buildTabs(perms, roleList), [perms, roleList])
  const [tab, setTab] = useState(() => buildTabs(perms, roleList)[0]?.id || 'structure')
  const [err, setErr] = useState('')
  const [msg, setMsg] = useState('')
  const [refreshKey, setRefreshKey] = useState(0)

  useEffect(() => {
    if (!tabs.length) return
    if (!tabs.some((t) => t.id === tab)) setTab(tabs[0].id)
  }, [tabs, tab])

  const flashOk = useCallback((text) => {
    setMsg(text)
    setErr('')
  }, [])

  const flashErr = useCallback((e, fallback) => {
    setErr(formatError(e) || fallback)
    setMsg('')
  }, [])

  if (!tabs.length) {
    return (
      <View style={styles.scroll}>
        <Text style={styles.h1}>Configuration</Text>
        <EmptyNote text="You do not have configuration permissions for this account." />
      </View>
    )
  }

  return (
    <ScrollView
      contentContainerStyle={styles.scroll}
      refreshControl={
        <RefreshControl refreshing={false} onRefresh={() => setRefreshKey((k) => k + 1)} />
      }
      keyboardShouldPersistTaps="handled"
    >
      <Text style={styles.h1}>Academic configuration</Text>
      <Text style={styles.hint}>Set up school structure, subjects, and student enrollment.</Text>
      {err ? <Text style={ui.err}>{err}</Text> : null}
      {msg ? <Text style={styles.ok}>{msg}</Text> : null}

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.tabRow}>
        {tabs.map((t) => (
          <Pressable
            key={t.id}
            onPress={() => {
              setTab(t.id)
              setErr('')
              setMsg('')
            }}
            style={[styles.tab, tab === t.id && styles.tabActive]}
          >
            <Text style={[styles.tabText, tab === t.id && styles.tabTextActive]}>{t.label}</Text>
          </Pressable>
        ))}
      </ScrollView>

      <View key={`${tab}-${refreshKey}`}>
        {tab === 'structure' ? <StructureTab flashOk={flashOk} flashErr={flashErr} /> : null}
        {tab === 'subjects' ? <SubjectsTab flashOk={flashOk} flashErr={flashErr} /> : null}
        {tab === 'assign' ? <AssignTab flashOk={flashOk} flashErr={flashErr} /> : null}
        {tab === 'enroll' ? <EnrollTab flashOk={flashOk} flashErr={flashErr} /> : null}
      </View>
    </ScrollView>
  )
}

const styles = StyleSheet.create({
  scroll: { padding: 16, paddingBottom: 40 },
  center: { marginTop: 24 },
  h1: { fontSize: 22, fontWeight: '700', marginBottom: 8, color: '#0f172a' },
  h2: { fontSize: 17, fontWeight: '700', marginBottom: 8, color: '#0f172a' },
  h3: { fontSize: 15, fontWeight: '700', color: '#0f172a' },
  hint: { fontSize: 13, color: '#64748b', marginBottom: 12, lineHeight: 18 },
  muted: { fontSize: 13, color: '#94a3b8', marginBottom: 8 },
  ok: { color: '#15803d', marginBottom: 12, lineHeight: 20 },
  tabRow: { gap: 8, marginBottom: 16 },
  tab: {
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 8,
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: '#e2e8f0',
  },
  tabActive: { backgroundColor: '#2563eb', borderColor: '#2563eb' },
  tabText: { fontWeight: '600', color: '#0f172a' },
  tabTextActive: { color: '#fff' },
  toolbar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 8,
    marginBottom: 12,
  },
  addBtn: {
    backgroundColor: '#2563eb',
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 8,
  },
  addBtnText: { color: '#fff', fontWeight: '600', fontSize: 13 },
  crumbRow: { alignItems: 'center', marginBottom: 12, gap: 4 },
  crumbItem: { flexDirection: 'row', alignItems: 'center' },
  crumb: { color: '#2563eb', fontWeight: '600', fontSize: 13 },
  crumbCurrent: { color: '#0f172a' },
  crumbSep: { color: '#94a3b8', marginHorizontal: 4 },
  formCard: {
    backgroundColor: '#fff',
    borderRadius: 10,
    padding: 14,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: '#e2e8f0',
  },
  formActions: { flexDirection: 'row', gap: 8, marginTop: 4 },
  label: { fontSize: 13, fontWeight: '600', color: '#64748b', marginBottom: 6 },
  labelInline: { fontSize: 13, fontWeight: '600', color: '#64748b' },
  input: {
    borderWidth: 1,
    borderColor: '#e2e8f0',
    borderRadius: 8,
    padding: 10,
    marginBottom: 12,
    backgroundColor: '#fff',
    color: '#0f172a',
  },
  inputDisabled: { backgroundColor: '#f8fafc', color: '#94a3b8' },
  switchRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 12,
  },
  pickerWrap: { marginBottom: 12 },
  disabled: { opacity: 0.55 },
  chipRow: { gap: 8 },
  chip: {
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 20,
    backgroundColor: '#f1f5f9',
    borderWidth: 1,
    borderColor: '#e2e8f0',
  },
  chipActive: { backgroundColor: '#dbeafe', borderColor: '#2563eb' },
  chipText: { fontSize: 14, color: '#334155' },
  chipTextActive: { color: '#1d4ed8', fontWeight: '600' },
  btn: {
    flex: 1,
    backgroundColor: '#2563eb',
    padding: 12,
    borderRadius: 8,
    alignItems: 'center',
    marginBottom: 12,
  },
  btnSecondary: {
    flex: 1,
    backgroundColor: '#f1f5f9',
    padding: 12,
    borderRadius: 8,
    alignItems: 'center',
    marginBottom: 12,
    borderWidth: 1,
    borderColor: '#e2e8f0',
  },
  btnDisabled: { opacity: 0.55 },
  btnText: { color: '#fff', fontWeight: '600' },
  btnSecondaryText: { color: '#334155', fontWeight: '600' },
  rowActions: { flexDirection: 'row', gap: 8, marginBottom: 10, marginTop: -4 },
  rowBtn: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 6,
    backgroundColor: '#f1f5f9',
  },
  rowBtnDanger: { backgroundColor: '#fef2f2' },
  rowBtnText: { color: '#334155', fontWeight: '600', fontSize: 13 },
  rowBtnDangerText: { color: '#b91c1c' },
  checkRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    paddingVertical: 10,
    paddingHorizontal: 12,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#e2e8f0',
    backgroundColor: '#fff',
    marginBottom: 8,
  },
  checkRowActive: { backgroundColor: '#eff6ff', borderColor: '#2563eb' },
  checkMark: { width: 18, fontWeight: '700', color: '#2563eb' },
  checkLabel: { flex: 1, color: '#0f172a', fontSize: 14 },
})
